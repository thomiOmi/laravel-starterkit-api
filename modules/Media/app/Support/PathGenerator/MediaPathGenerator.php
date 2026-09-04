<?php

declare(strict_types=1);

namespace Modules\Media\Support\PathGenerator;

use Modules\Media\Models\Media;

interface MediaPathGenerator
{
    public function getPath(Media $media): string;

    public function getPathForConversions(Media $media, string $conversion): string;

    public function getPathForResponsiveImages(Media $media): string;
}
