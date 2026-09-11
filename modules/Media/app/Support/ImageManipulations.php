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
            if (! is_string($filter)) {
                throw new InvalidArgumentException('Image filter must be a string.');
            }

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

            if (! is_int($level)) {
                throw new InvalidArgumentException("Image {$operation} level must be an integer.");
            }

            if ($level < 0 || $level > 100) {
                throw new InvalidArgumentException("Image {$operation} level must be between 0 and 100.");
            }

            $image = $operation === 'blur'
                ? $image->blur($level)
                : $image->sharpen($level);
        }

        if (array_key_exists('rotate', $manipulations)) {
            $angle = $manipulations['rotate'];

            if (! is_int($angle) && ! is_float($angle)) {
                throw new InvalidArgumentException('Image rotation angle must be numeric.');
            }

            $image = $image->rotate((float) $angle);
        }

        return $image;
    }
}
