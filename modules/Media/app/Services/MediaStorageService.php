<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaPrefix;
use Modules\Media\Support\StorageOptions;

/**
 * Handles physical file storage for media.
 */
final readonly class MediaStorageService
{
    /**
     * Store the uploaded file on the given disk and directory.
     */
    public function store(UploadedFile $file, string $disk, string $directory, string $visibility): string
    {
        $filename = $file->hashName();
        $path = MediaPrefix::join($directory, $filename);

        Storage::disk($disk)->putFileAs(MediaPrefix::join($directory), $file, $filename, StorageOptions::forVisibility($visibility));

        return $path;
    }

    /**
     * Delete the media file from storage.
     */
    public function delete(Media $media): void
    {
        $path = $media->getPath();

        if (is_string($path)) {
            Storage::disk($media->disk)->delete($path);
        }
    }

    /**
     * Delete all variant files for the media.
     */
    public function deleteVariants(Media $media): void
    {
        Storage::disk($media->disk)->deleteDirectory(MediaPrefix::join('variants', (string) $media->id));
    }

    /**
     * Check whether the media file exists on storage.
     */
    public function exists(Media $media): bool
    {
        $path = $media->getPath();

        return is_string($path) && Storage::disk($media->disk)->exists($path);
    }

    /**
     * Get the absolute path for the media file, or null for remote disks.
     */
    public function path(Media $media): ?string
    {
        $disk = Storage::disk($media->disk);
        $path = $media->getPath();

        if (! is_string($path)) {
            return null;
        }

        try {
            return $disk->path($path);
        } catch (\Throwable) {
            return null;
        }
    }
}
