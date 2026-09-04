<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Single source for storage paths under the optional media prefix.
 *
 * Reads and writes must agree: DefaultPathGenerator, UploadMediaAction,
 * conversions, variants, and cleanup all build paths through here, so
 * setting media.prefix moves everything at once. External consumers
 * resolve paths through the Media model (the public seam), never by
 * importing this internal helper.
 */
final class MediaPrefix
{
    public static function prefix(): string
    {
        return trim(config()->string('media.prefix', ''));
    }

    public static function join(string ...$segments): string
    {
        $parts = [];

        foreach ($segments as $segment) {
            $trimmed = trim($segment, '/');

            if ($trimmed !== '') {
                $parts[] = $trimmed;
            }
        }

        $prefix = self::prefix();

        if ($prefix !== '') {
            array_unshift($parts, trim($prefix, '/'));
        }

        return implode('/', $parts);
    }

    public static function basePath(string $collectionName, string $fileName): string
    {
        return self::join($collectionName, $fileName);
    }

    public static function directory(string $collectionName): string
    {
        return self::join($collectionName);
    }
}
