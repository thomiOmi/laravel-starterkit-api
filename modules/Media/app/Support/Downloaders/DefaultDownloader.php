<?php

declare(strict_types=1);

namespace Modules\Media\Support\Downloaders;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

final readonly class DefaultDownloader implements MediaDownloader
{
    #[\Override]
    public function download(string $url, array $headers = []): array
    {
        try {
            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => config()->boolean('media.media_downloader_ssl', true)])
                ->timeout(config()->integer('media.downloader_timeout', 10))
                ->get($url);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException("Failed to fetch URL: {$url}", previous: $exception);
        }

        if (! $response->successful()) {
            throw new InvalidArgumentException("Failed to fetch URL: {$url} (HTTP {$response->status()})");
        }

        $content = $response->body();

        if ($content === '') {
            throw new InvalidArgumentException("Failed to fetch URL: {$url} (empty body)");
        }

        $rawPath = parse_url($url, PHP_URL_PATH);
        $path = is_string($rawPath) && $rawPath !== '' ? $rawPath : 'file';

        return ['content' => $content, 'filename' => basename($path)];
    }
}
