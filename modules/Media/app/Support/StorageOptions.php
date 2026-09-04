<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Single source for filesystem write options.
 *
 * Merges the visibility with the optional media.remote.extra_headers
 * (e.g. S3 CacheControl) for every stored file.
 */
final class StorageOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function forVisibility(string $visibility): array
    {
        $options = ['visibility' => $visibility];

        foreach (config()->array('media.remote.extra_headers', []) as $key => $value) {
            if (is_string($key) && $key !== '' && is_string($value)) {
                $options[$key] = $value;
            }
        }

        return $options;
    }
}
