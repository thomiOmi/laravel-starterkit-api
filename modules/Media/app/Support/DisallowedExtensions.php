<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Guards file names against executable or scriptable extensions.
 *
 * Every dot-separated segment is inspected, so shell.php.jpg is
 * rejected even though its final extension looks harmless.
 */
final readonly class DisallowedExtensions
{
    public static function contains(string $filename): bool
    {
        $denied = [];

        foreach (config()->array('media.disallowed_extensions', []) as $extension) {
            if (is_string($extension) && $extension !== '') {
                $denied[] = strtolower($extension);
            }
        }

        if ($denied === []) {
            return false;
        }

        $segments = explode('.', strtolower(basename($filename)));
        array_shift($segments);

        foreach ($segments as $segment) {
            if ($segment !== '' && in_array($segment, $denied, true)) {
                return true;
            }
        }

        return false;
    }
}
