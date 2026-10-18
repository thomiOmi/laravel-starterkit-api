<?php

declare(strict_types=1);

namespace Modules\Media\Support\PathGenerator;

use Modules\Media\Models\Media;
use Modules\Media\Support\MediaPrefix;

final readonly class DefaultPathGenerator implements MediaPathGenerator
{
    public function getPath(Media $media): string
    {
        return MediaPrefix::basePath($media->collection_name, $media->file_name);
    }

    public function getPathForConversions(Media $media): string
    {
        return MediaPrefix::join('conversions', (string) $media->id);
    }

    public function getPathForDerivedConversions(Media $media): string
    {
        return MediaPrefix::join('conversions/derived', (string) $media->id);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return dirname($this->getPath($media)).'/responsive-images';
    }
}
