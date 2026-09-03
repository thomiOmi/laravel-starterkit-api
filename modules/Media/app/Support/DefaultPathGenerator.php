<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Modules\Media\Contracts\PathGenerator;
use Modules\Media\Models\Media;

final readonly class DefaultPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $media->path;
    }

    public function getPathForConversions(Media $media, string $conversion): string
    {
        return dirname($media->path).'/conversions/'.$conversion.'/'.basename($media->path);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return dirname($media->path).'/responsive-images/'.basename($media->path);
    }
}
