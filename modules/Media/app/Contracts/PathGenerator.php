<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Modules\Media\Models\Media;

interface PathGenerator
{
    public function getPath(Media $media): string;

    public function getPathForConversions(Media $media, string $conversion): string;

    public function getPathForResponsiveImages(Media $media): string;
}
