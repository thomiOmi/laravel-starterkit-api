<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;

final class RecordingFileRemover implements MediaFileRemover
{
    /** @var array<int, string> */
    public static array $removed = [];

    public function removeAllFiles(Media $media): void
    {
        self::$removed[] = (string) $media->id;
    }

    public function removeResponsiveImages(Media $media): void
    {
        // No-op for the recording stub.
    }

    public function removeFile(string $path, string $disk): void
    {
        // No-op for the recording stub.
    }
}
