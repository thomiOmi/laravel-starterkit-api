<?php

declare(strict_types=1);

namespace Modules\Media\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Modules\Media\Support\ImageDimensions;
use Modules\Media\Support\MediaMimeType;

/**
 * Rejects images whose probed dimensions exceed the configured limits.
 *
 * Probing uses getimagesize (no full decode) so decompression bombs are
 * rejected before the GD/Imagick pipeline allocates memory. Non-images
 * always pass; the mime rules own those.
 */
final class ImageDimensionLimits implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $realPath = $value->getRealPath();

        if (! is_string($realPath) || $realPath === '') {
            return;
        }

        $detected = MediaMimeType::detect($realPath);

        if ($detected === null || ! MediaMimeType::isImage($detected)) {
            return;
        }

        $maxWidth = config()->integer('media.image.max_width', 8000);
        $maxHeight = config()->integer('media.image.max_height', 8000);
        $maxPixels = config()->integer('media.image.max_pixels', 25000000);

        if (ImageDimensions::exceedsLimits($realPath, $maxWidth, $maxHeight, $maxPixels)) {
            $fail(__('validation.media_dimensions_exceeded'));
        }
    }
}
