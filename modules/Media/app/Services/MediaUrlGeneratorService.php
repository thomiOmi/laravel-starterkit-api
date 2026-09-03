<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Media\Contracts\MediaUrlGenerator;
use Modules\Media\Models\Media;

/**
 * Default URL generator for media files.
 */
final readonly class MediaUrlGeneratorService implements MediaUrlGenerator
{
    #[\Override]
    public function getUrl(Media $media): ?string
    {
        return $media->url();
    }

    #[\Override]
    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string
    {
        $disk = Storage::disk($media->disk);
        $path = $media->getPath();

        if (is_string($path)) {
            try {
                return $disk->temporaryUrl($path, $expiration);
            } catch (\Throwable) {
                // Fall back to signed route for local disks.
            }
        }

        return (string) URL::temporarySignedRoute(
            'api.v1.media.file',
            $expiration,
            ['media' => $media->getKey()],
        );
    }

    #[\Override]
    public function getTemporaryUrlForMinutes(Media $media, int $ttlMinutes): string
    {
        return $this->getTemporaryUrl($media, now()->addMinutes($ttlMinutes));
    }
}
