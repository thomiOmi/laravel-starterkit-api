<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Modules\Media\Models\Media;
use Modules\Media\Support\PathGenerator\MediaPathGenerator;

/**
 * @deprecated Use Modules\Media\Support\PathGenerator\DefaultPathGenerator instead.
 */
final readonly class DefaultPathGenerator implements MediaPathGenerator
{
    private readonly PathGenerator\DefaultPathGenerator $inner;

    public function __construct()
    {
        $this->inner = new PathGenerator\DefaultPathGenerator;
    }

    public function getPath(Media $media): string
    {
        return $this->inner->getPath($media);
    }

    public function getPathForConversions(Media $media, string $conversion): string
    {
        return $this->inner->getPathForConversions($media, $conversion);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->inner->getPathForResponsiveImages($media);
    }
}
