<?php

declare(strict_types=1);

namespace Modules\Media\Support\PathGenerator;

use Modules\Media\Models\Media;

interface MediaPathGenerator
{
    /**
     * Original file path.
     */
    public function getPath(Media $media): string;

    /**
     * Directory for eager named conversions.
     */
    public function getPathForConversions(Media $media): string;

    /**
     * Directory for the on-demand derived conversion cache.
     */
    public function getPathForDerivedConversions(Media $media): string;

    /**
     * Directory for responsive image siblings.
     */
    public function getPathForResponsiveImages(Media $media): string;
}
