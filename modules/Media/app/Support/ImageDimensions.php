<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Fast image dimension probing without a full decode.
 *
 * Uses getimagesize on the real file so oversized images are rejected
 * before the GD/Imagick pipeline allocates memory (decompression-bomb
 * protection). Returns null for non-images or unreadable files.
 */
final class ImageDimensions
{
    /**
     * @return array{width: int, height: int}|null
     */
    public static function measure(string $realPath): ?array
    {
        $size = @getimagesize($realPath);

        if ($size === false) {
            return null;
        }

        $width = (int) $size[0];
        $height = (int) $size[1];

        if ($width < 1 || $height < 1) {
            return null;
        }

        return ['width' => $width, 'height' => $height];
    }

    public static function exceedsLimits(string $realPath, int $maxWidth, int $maxHeight, int $maxPixels): bool
    {
        $dimensions = self::measure($realPath);

        if ($dimensions === null) {
            return false;
        }

        if ($dimensions['width'] > $maxWidth || $dimensions['height'] > $maxHeight) {
            return true;
        }

        return $maxPixels < $dimensions['width'] * $dimensions['height'];
    }
}
