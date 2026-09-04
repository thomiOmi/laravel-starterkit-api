<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use DateTimeInterface;
use Modules\Media\Models\Media;
use Modules\Media\Support\UrlGenerator\DefaultUrlGenerator;
use Modules\Media\Support\UrlGenerator\MediaUrlGenerator;

/**
 * @deprecated Use Modules\Media\Support\UrlGenerator\DefaultUrlGenerator instead.
 */
final readonly class MediaUrlGeneratorService implements MediaUrlGenerator
{
    private readonly DefaultUrlGenerator $inner;

    public function __construct()
    {
        $this->inner = new DefaultUrlGenerator;
    }

    public function getUrl(Media $media): ?string
    {
        return $this->inner->getUrl($media);
    }

    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string
    {
        return $this->inner->getTemporaryUrl($media, $expiration);
    }

    public function getTemporaryUrlForMinutes(Media $media, int $ttlMinutes): string
    {
        return $this->inner->getTemporaryUrlForMinutes($media, $ttlMinutes);
    }
}
