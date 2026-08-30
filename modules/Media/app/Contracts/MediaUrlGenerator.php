<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use DateTimeInterface;
use Modules\Media\Models\Media;

/**
 * Generates URLs for media files.
 */
interface MediaUrlGenerator
{
    /**
     * Get the public URL for the media, or null for private media.
     */
    public function getUrl(Media $media): ?string;

    /**
     * Get a temporary signed URL for the media.
     */
    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string;

    /**
     * Get a temporary signed URL valid for the given minutes.
     */
    public function getTemporaryUrlForMinutes(Media $media, int $ttlMinutes): string;
}
