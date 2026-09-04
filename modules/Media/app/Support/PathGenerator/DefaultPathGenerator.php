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

    public function getPathForConversions(Media $media, string $conversion): string
    {
        $base = $this->getPath($media);

        return dirname($base).'/conversions/'.$conversion.'/'.basename($base);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        $base = $this->getPath($media);

        return dirname($base).'/responsive-images/'.basename($base);
    }
}
