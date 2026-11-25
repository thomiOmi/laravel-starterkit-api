<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Exception;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;
use Modules\Media\Support\FileRemover\MediaFilesSnapshot;

final class ThrowingFileRemover implements MediaFileRemover
{
    public static int $calls = 0;

    public function removeAllFiles(Media $media): void
    {
        throw new Exception('storage down');
    }

    public function removeCapturedFiles(MediaFilesSnapshot $snapshot): void
    {
        self::$calls++;

        throw new Exception('storage down');
    }

    public function removeResponsiveImages(Media $media): void
    {
        throw new Exception('storage down');
    }

    public function removeFile(string $path, string $disk): void
    {
        throw new Exception('storage down');
    }
}
