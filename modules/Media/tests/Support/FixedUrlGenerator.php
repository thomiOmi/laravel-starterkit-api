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
        return 'https://custom.example.com/'.$media->getPath();
    }

    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string
    {
        return 'https://custom.example.com/signed/'.$media->id.'?expires='.$expiration->getTimestamp();
    }

    public function getTemporaryUrlForMinutes(Media $media, int $ttlMinutes): string
    {
        return 'https://custom.example.com/signed/'.$media->id.'?expires='.(time() + $ttlMinutes * 60);
    }
}
