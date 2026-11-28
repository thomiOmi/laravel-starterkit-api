<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Cheap, bounded detection of animated image containers.
 *
 * Only a fixed prefix is read. Ambiguous or truncated input is treated as
 * non-animated so the existing pipeline decides, never an unbounded parse.
 */
final readonly class AnimatedImage
{
    private const int MAX_BYTES = 65536;

    private const int MAX_STEPS = 4096;

    public static function fromPath(string $path): bool
    {
        $stream = @fopen($path, 'rb');

        if ($stream === false) {
            return false;
        }

        try {
            return self::fromStream($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  resource  $stream
     */
    public static function fromStream(mixed $stream): bool
    {
        $data = fread($stream, self::MAX_BYTES);

        if (! is_string($data) || $data === '') {
            return false;
        }

        if (str_starts_with($data, 'GIF87a') || str_starts_with($data, 'GIF89a')) {
            return self::animatedGif($data);
        }

        if (str_starts_with($data, "\x89PNG\r\n\x1a\n")) {
            return self::animatedPng($data);
        }

        if (str_starts_with($data, 'RIFF') && substr($data, 8, 4) === 'WEBP') {
            return self::animatedWebp($data);
        }

        return false;
    }

    private static function animatedGif(string $data): bool
    {
        $length = strlen($data);

        if ($length < 13) {
            return false;
        }

        $packed = ord($data[10]);
        $position = 13 + (($packed & 0x80) === 0 ? 0 : 3 * (2 ** (($packed & 0x07) + 1)));
        $frames = 0;

        for ($steps = 0; $steps < self::MAX_STEPS && $position < $length; $steps++) {
            $marker = ord($data[$position]);

            if ($marker === 0x3B) {
                return false;
            }

            if ($marker === 0x2C) {
                $frames++;

                if ($frames > 1) {
                    return true;
                }

                $position += 10;
                $local = ord($data[$position - 1] ?? "\0");

                if (($local & 0x80) !== 0) {
                    $position += 3 * (2 ** (($local & 0x07) + 1));
                }

                $position++;
            } elseif ($marker === 0x21) {
                $position += 2;
            } else {
                return false;
            }

            $position = self::skipGifBlocks($data, $position);

            if ($position < 0) {
                return false;
            }
        }

        return false;
    }

    private static function skipGifBlocks(string $data, int $position): int
    {
        $length = strlen($data);

        for ($steps = 0; $steps < self::MAX_STEPS && $position < $length; $steps++) {
            $size = ord($data[$position]);

            if ($size === 0) {
                return $position + 1;
            }

            $position += 1 + $size;
        }

        return -1;
    }

    private static function animatedPng(string $data): bool
    {
        $length = strlen($data);
        $position = 8;

        for ($steps = 0; $steps < self::MAX_STEPS && $position + 8 <= $length; $steps++) {
            $unpacked = unpack('N', substr($data, $position, 4));

            $chunkLength = $unpacked[1] ?? null;

            if (! is_int($chunkLength)) {
                return false;
            }

            $type = substr($data, $position + 4, 4);

            if ($type === 'acTL') {
                return true;
            }

            if ($type === 'IDAT') {
                return false;
            }

            $next = $position + 12 + $chunkLength;

            if ($next <= $position || $next > $length) {
                return false;
            }

            $position = $next;
        }

        return false;
    }

    private static function animatedWebp(string $data): bool
    {
        $length = strlen($data);
        $position = 12;

        for ($steps = 0; $steps < self::MAX_STEPS && $position + 8 <= $length; $steps++) {
            if (substr($data, $position, 4) === 'ANIM') {
                return true;
            }

            $unpacked = unpack('V', substr($data, $position + 4, 4));

            $chunkSize = $unpacked[1] ?? null;

            if (! is_int($chunkSize)) {
                return false;
            }

            $next = $position + 8 + $chunkSize + ($chunkSize % 2);

            if ($next <= $position || $next > $length) {
                return false;
            }

            $position = $next;
        }

        return false;
    }
}
