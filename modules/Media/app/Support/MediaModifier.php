<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * @deprecated Use MediaConversion::parse() / MediaConversion::fromModifiers() instead.
 */
final readonly class MediaModifier
{
    /**
     * @return array{w?: int, h?: int, s?: string, f?: string, q?: int, format?: string, width?: int, height?: int}
     */
    public static function parse(string $modifiers): array
    {
        return MediaConversion::parse($modifiers);
    }

    /**
     * @param  array<string, mixed>  $modifiers
     */
    public static function toCacheKey(array $modifiers, string $version): string
    {
        return MediaConversion::toCacheKey($modifiers, $version);
    }
}
