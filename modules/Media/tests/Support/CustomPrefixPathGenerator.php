<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Modules\Media\Models\Media;
use Modules\Media\Support\PathGenerator\MediaPathGenerator;

final class CustomPrefixPathGenerator implements MediaPathGenerator
{
    public function getPath(Media $media): string
    {
        return 'custom-prefix/'.$media->collection_name.'/'.$media->file_name;
    }

    public function getPathForConversions(Media $media, string $conversion): string
    {
        return 'custom-prefix/'.$media->collection_name.'/conversions/'.$conversion.'/'.$media->file_name;
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return 'custom-prefix/'.$media->collection_name.'/responsive-images/'.$media->file_name;
    }
}
