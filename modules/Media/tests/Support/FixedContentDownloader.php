<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Illuminate\Http\UploadedFile;
use Modules\Media\Support\Downloaders\MediaDownloader;

final class FixedContentDownloader implements MediaDownloader
{
    public function download(string $url, array $headers = []): array
    {
        $jpeg = (string) UploadedFile::fake()->image('fixed.jpg', 20, 20)->getContent();

        return ['content' => $jpeg, 'filename' => basename((string) parse_url($url, PHP_URL_PATH))];
    }
}
