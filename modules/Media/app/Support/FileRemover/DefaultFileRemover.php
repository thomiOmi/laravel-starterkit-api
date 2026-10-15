<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileRemover;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

final readonly class DefaultFileRemover implements MediaFileRemover
{
    #[\Override]
    public function removeAllFiles(Media $media): void
    {
        $path = $media->getPath();

        if (is_string($path)) {
            $this->removeFile($path, $media->disk);
        }

        // Reuse eager-loaded conversions relation when present to avoid an extra database query during deletion.
        $conversions = $media->relationLoaded('conversions')
            ? $media->conversions
            : $media->conversions()->get();

        foreach ($conversions as $conversion) {
            $this->removeFile($conversion->path, $conversion->disk);
        }

        $this->removeResponsiveImages($media);

        Storage::disk($media->conversions_disk ?? $media->disk)->deleteDirectory($media->getDerivedConversionsDirectory());
        Storage::disk($media->conversions_disk ?? $media->disk)->deleteDirectory($media->getConversionsDirectory());
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
