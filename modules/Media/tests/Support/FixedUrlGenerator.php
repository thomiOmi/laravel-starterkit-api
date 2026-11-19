<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use DateTimeInterface;
use Modules\Media\Models\Media;
use Modules\Media\Support\UrlGenerator\MediaUrlGenerator;

final class FixedUrlGenerator implements MediaUrlGenerator
{
    public function getUrl(Media $media): string
    {
        return 'https://cdn.example.test/'.$media->file_name;
    }

    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string
    {
        return 'https://cdn.example.test/'.$media->file_name.'?expires='.$expiration->getTimestamp();
    }

    public function getTemporaryUrlForMinutes(Media $media, int $ttlMinutes): string
    {
        return $this->getTemporaryUrl($media, now()->addMinutes($ttlMinutes));
    }
}
