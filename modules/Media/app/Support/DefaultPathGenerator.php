<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Modules\Media\Contracts\PathGenerator;
use Modules\Media\Models\Media;

final readonly class DefaultPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $media->collection_name.'/'.$media->file_name;
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
