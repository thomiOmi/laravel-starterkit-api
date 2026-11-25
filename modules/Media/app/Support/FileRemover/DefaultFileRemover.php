<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileRemover;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Override;
use Throwable;

final readonly class DefaultFileRemover implements MediaFileRemover
{
    #[Override]
    public function removeAllFiles(Media $media): void
    {
        $this->removeCapturedFiles(MediaFilesSnapshot::capture($media));
    }

    #[Override]
    public function removeCapturedFiles(MediaFilesSnapshot $snapshot): void
    {
        foreach ($snapshot->files as $file) {
            try {
                $this->removeFile($file['path'], $file['disk']);
            } catch (Throwable $throwable) {
                $this->reportFailure($snapshot->mediaId, $file['path'], $file['disk'], $throwable);
            }
        }

        foreach ($snapshot->directories as $directory) {
            try {
                Storage::disk($directory['disk'])->deleteDirectory($directory['directory']);
            } catch (Throwable $throwable) {
                $this->reportFailure($snapshot->mediaId, $directory['directory'], $directory['disk'], $throwable);
            }
        }
    }

    #[Override]
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

    #[Override]
    public function removeFile(string $path, string $disk): void
    {
        Storage::disk($disk)->delete($path);
    }

    /**
     * Storage failures leave an orphan, never a failed request or a silent miss.
     */
    private function reportFailure(string $mediaId, string $path, string $disk, Throwable $throwable): void
    {
        Log::warning('Media file deletion failed.', [
            'media_id' => $mediaId,
            'path' => $path,
            'disk' => $disk,
            'error' => $throwable->getMessage(),
        ]);
    }
}
