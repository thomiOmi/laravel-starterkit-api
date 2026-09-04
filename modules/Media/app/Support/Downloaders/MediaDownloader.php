<?php

declare(strict_types=1);

namespace Modules\Media\Support\Downloaders;

interface MediaDownloader
{
    /**
     * Download a remote file.
     *
     * @param  array<string, string>  $headers
     * @return array{content: string, filename: string}
     *
     * @throws \InvalidArgumentException When the file cannot be fetched.
     */
    public function download(string $url, array $headers = []): array;
}
