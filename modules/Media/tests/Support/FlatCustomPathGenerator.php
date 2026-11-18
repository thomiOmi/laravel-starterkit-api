<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Modules\Media\Models\Media;
use Modules\Media\Support\PathGenerator\MediaPathGenerator;

final class FlatCustomPathGenerator implements MediaPathGenerator
{
    public function getPath(Media $media): string
    {
        return 'custom/'.$media->file_name;
    }

    public function getPathForConversions(Media $media): string
    {
        return 'custom/conversions/'.$media->id;
    }

    public function getPathForDerivedConversions(Media $media): string
    {
        return 'custom/conversions/derived/'.$media->id;
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return dirname($this->getPath($media)).'/responsive-images';
    }
}
