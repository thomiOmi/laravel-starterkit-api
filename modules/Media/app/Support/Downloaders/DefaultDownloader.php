<?php

declare(strict_types=1);

namespace Modules\Media\Support\Downloaders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final readonly class DefaultDownloader implements MediaDownloader
{
    #[\Override]
    public function download(string $url, array $headers = []): array
    {
        $this->guardUrl($url);

        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => config()->boolean('media.media_downloader_ssl', true),
                    'allow_redirects' => ['max' => 3],
                ])
                ->timeout(config()->integer('media.downloader_timeout', 10))
                ->get($url);
        } catch (Throwable $exception) {
            Log::warning('Media download failed.', ['url' => $url, 'error' => $exception->getMessage()]);

            throw new InvalidArgumentException('Failed to fetch remote file.');
        }

        if (! $response->successful()) {
            Log::warning('Media download failed.', ['url' => $url, 'status' => $response->status()]);

            throw new InvalidArgumentException('Failed to fetch remote file.');
        }

        $content = $response->body();
        $maxBytes = config()->integer('media.max_size') * 1024;

        if ($content === '' || strlen($content) > $maxBytes) {
            throw new InvalidArgumentException('Failed to fetch remote file.');
        }

        $rawPath = parse_url($url, PHP_URL_PATH);
        $path = is_string($rawPath) && $rawPath !== '' ? $rawPath : 'file';

        return ['content' => $content, 'filename' => basename($path)];
    }

    /**
     * Reject non-public targets before any request is made. DNS
     * resolution is point-in-time: a rebinding host between check
     * and request is a documented residual risk.
     */
    private function guardUrl(string $url): void
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw new InvalidArgumentException('Failed to fetch remote file.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $allowHttp = config()->boolean('media.downloader_allow_http', false);

        if ($scheme !== 'https' && ! ($allowHttp && $scheme === 'http')) {
            throw new InvalidArgumentException('Failed to fetch remote file.');
        }

        $host = (string) ($parts['host'] ?? '');

        if ($host === '' || $this->isBlockedHost($host)) {
            throw new InvalidArgumentException('Failed to fetch remote file.');
        }
    }

    private function isBlockedHost(string $host): bool
    {
        $lower = strtolower($host);

        if ($lower === 'localhost' || str_ends_with($lower, '.localhost') || str_ends_with($lower, '.local')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        // Unresolvable hosts fail open: the request itself will fail
        // naturally. Only positively-identified private targets block.
        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (! is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            }
        }

        return false;
    }
}
