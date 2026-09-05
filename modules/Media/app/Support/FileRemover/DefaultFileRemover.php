<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileRemover;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaPrefix;

final readonly class DefaultFileRemover implements MediaFileRemover
{
    #[\Override]
    public function removeAllFiles(Media $media): void
    {
        $path = $media->getPath();

        if (is_string($path)) {
            $this->removeFile($path, $media->disk);
        }

        foreach ($media->conversions()->get() as $conversion) {
            $this->removeFile($conversion->path, $conversion->disk);
        }

        $this->removeResponsiveImages($media);

        Storage::disk($media->disk)->deleteDirectory(MediaPrefix::join('variants', (string) $media->id));
        Storage::disk($media->conversions_disk ?? $media->disk)->deleteDirectory(MediaPrefix::join('conversions', (string) $media->id));
    }

    #[\Override]
    public function removeResponsiveImages(Media $media): void
    {
        $responsive = $media->responsive_images;

        if (! is_array($responsive)) {
            return;
        }

        foreach ($responsive as $info) {
            if ($info['path'] === '') {
                continue;
            }

            $this->removeFile($info['path'], $media->conversions_disk ?? $media->disk);
        }
    }

    #[\Override]
    public function removeFile(string $path, string $disk): void
    {
        Storage::disk($disk)->delete($path);
    }
}
