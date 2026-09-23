<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Illuminate\Image\Image;
use InvalidArgumentException;

/**
 * Applies the supported image manipulations to an image instance.
 */
final class ImageManipulations
{
    /**
     * @param  array<string, mixed>  $manipulations
     */
    public static function apply(Image $image, array $manipulations): Image
    {
        $filter = $manipulations['filter'] ?? null;

        if ($filter !== null) {
            throw_unless(is_string($filter), InvalidArgumentException::class, 'Image filter must be a string.');

            $image = match (strtolower($filter)) {
                'grayscale' => $image->grayscale(),
                default => throw new InvalidArgumentException("Unsupported image filter [{$filter}]."),
            };
        }

        if (($manipulations['grayscale'] ?? false) === true) {
            $image = $image->grayscale();
        }

        foreach (['blur', 'sharpen'] as $operation) {
            if (! array_key_exists($operation, $manipulations)) {
                continue;
            }

            $level = $manipulations[$operation];

            throw_unless(is_int($level), InvalidArgumentException::class, "Image {$operation} level must be an integer.");

            throw_if($level < 0 || $level > 100, InvalidArgumentException::class, "Image {$operation} level must be between 0 and 100.");

            $image = $operation === 'blur'
                ? $image->blur($level)
                : $image->sharpen($level);
        }

        if (array_key_exists('rotate', $manipulations)) {
            $angle = $manipulations['rotate'];

            throw_if(! is_int($angle) && ! is_float($angle), InvalidArgumentException::class, 'Image rotation angle must be numeric.');

            $image = $image->rotate((float) $angle);
        }

        return $image;
    }
}
