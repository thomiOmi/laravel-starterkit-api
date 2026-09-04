<?php

declare(strict_types=1);

namespace Modules\Media\Support\UrlGenerator;

use DateTimeInterface;
use Modules\Media\Models\Media;

interface MediaUrlGenerator
{
    public function getUrl(Media $media): ?string;

    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string;

    public function getTemporaryUrlForMinutes(Media $media, int $ttlMinutes): string;
}
