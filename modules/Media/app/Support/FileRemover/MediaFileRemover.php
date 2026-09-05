<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileRemover;

use Modules\Media\Models\Media;

interface MediaFileRemover
{
    /**
     * Remove all files relating to the media item.
     */
    public function removeAllFiles(Media $media): void;

    /**
     * Remove responsive files relating to the media item.
     */
    public function removeResponsiveImages(Media $media): void;

    /**
     * Remove a single file from the given disk.
     */
    public function removeFile(string $path, string $disk): void;
}
