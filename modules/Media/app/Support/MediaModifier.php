<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use InvalidArgumentException;

/**
 * Parses media modifiers from a path segment (variant on-the-fly).
 *
 * Distinct from MediaConversion (persisted, named conversions like thumbnail).
 * Supports both styles for backward compatibility:
 * - IPX style: "w_320", "w_320,h_200", "s_320x200,f_webp,q_80"
 * - Slash style: "320", "320x200", "320/f/webp", "w/320/f/webp/q/80", "s/320"
 */
final readonly class MediaModifier
{
    /**
     * @return array{w?: int, h?: int, s?: string, f?: string, q?: int, fit?: string, kernel?: string, format?: string, width?: int, height?: int}
     */
    public static function parse(string $modifiers): array
    {
        $modifiers = trim($modifiers, '/');

        if ($modifiers === '') {
            throw new InvalidArgumentException('Modifiers cannot be empty.');
        }

        $result = [];
        $isIpxStyle = str_contains($modifiers, ',') || str_contains($modifiers, '_');

        if ($isIpxStyle) {
            foreach (explode(',', $modifiers) as $part) {
                $part = trim($part);

                if ($part === '') {
                    continue;
                }

                [$key, $value] = array_pad(explode('_', $part, 2), 2, null);

                if ($value === null) {
                    continue;
                }

                $key = strtolower((string) $key);
                $value = trim((string) $value);

                switch ($key) {
                    case 's':
                    case 'resize':
                        $result['s'] = $value;
                        if (str_contains($value, 'x')) {
                            [$w, $h] = explode('x', $value, 2);
                            $parsedW = self::parseInt($w);
                            $parsedH = self::parseInt($h);
                            if ($parsedW !== null) {
                                $result['w'] = $parsedW;
                            }
                            if ($parsedH !== null) {
                                $result['h'] = $parsedH;
                            }
                        } else {
                            $parsedW = self::parseInt($value);
                            if ($parsedW !== null) {
                                $result['w'] = $parsedW;
                            }
                        }
                        break;
                    case 'w':
                    case 'width':
                        $parsedW = self::parseInt($value);
                        if ($parsedW !== null) {
                            $result['w'] = $parsedW;
                        }
                        break;
                    case 'h':
                    case 'height':
                        $parsedH = self::parseInt($value);
                        if ($parsedH !== null) {
                            $result['h'] = $parsedH;
                        }
                        break;
                    case 'f':
                    case 'format':
                        $result['f'] = strtolower($value);
                        $result['format'] = strtolower($value);
                        break;
                    case 'q':
                    case 'quality':
                        $parsedQ = self::parseInt($value);
                        if ($parsedQ !== null) {
                            $result['q'] = $parsedQ;
                        }
                        break;
                    case 'fit':
                        $result['fit'] = strtolower($value);
                        break;
                    case 'kernel':
                        $result['kernel'] = strtolower($value);
                        break;
                }
            }
        } else {
            $parts = explode('/', $modifiers);
            $first = $parts[0];

            if (preg_match('/^\d+$/', $first) === 1) {
                $result['w'] = (int) $first;
                array_shift($parts);
            } elseif (preg_match('/^\d+x\d+$/', $first) === 1) {
                [$w, $h] = explode('x', $first, 2);
                $result['w'] = (int) $w;
                $result['h'] = (int) $h;
                array_shift($parts);
            } elseif ($first === 's' && isset($parts[1]) && preg_match('/^\d+$/', $parts[1]) === 1) {
                $result['s'] = $parts[1];
                $result['w'] = (int) $parts[1];
                array_shift($parts);
                array_shift($parts);
            } elseif ($first === 's' && isset($parts[1]) && preg_match('/^\d+x\d+$/', $parts[1]) === 1) {
                $result['s'] = $parts[1];
                [$w, $h] = explode('x', $parts[1], 2);
                $result['w'] = (int) $w;
                $result['h'] = (int) $h;
                array_shift($parts);
                array_shift($parts);
            }

            for ($i = 0; $i < count($parts); $i += 2) {
                $key = $parts[$i];
                $value = $parts[$i + 1] ?? null;

                if ($value === null) {
                    continue;
                }

                $key = strtolower($key);
                $value = strtolower($value);

                switch ($key) {
                    case 'w':
                        $result['w'] = (int) $value;
                        break;
                    case 'h':
                        $result['h'] = (int) $value;
                        break;
                    case 's':
                        $result['s'] = $value;
                        if (str_contains($value, 'x')) {
                            [$w, $h] = explode('x', $value, 2);
                            $result['w'] = (int) $w;
                            $result['h'] = (int) $h;
                        } else {
                            $result['w'] = (int) $value;
                        }
                        break;
                    case 'f':
                    case 'format':
                        $result['f'] = $value;
                        $result['format'] = $value;
                        break;
                    case 'q':
                    case 'quality':
                        $result['q'] = (int) $value;
                        break;
                    case 'fit':
                        $result['fit'] = strtolower($value);
                        break;
                    case 'kernel':
                        $result['kernel'] = strtolower($value);
                        break;
                }
            }
        }

        if (array_key_exists('format', $result) && ! array_key_exists('f', $result)) {
            $result['f'] = $result['format'];
        }

        if (array_key_exists('f', $result) && ! array_key_exists('format', $result)) {
            $result['format'] = $result['f'];
        }

        if (array_key_exists('w', $result)) {
            $result['width'] = $result['w'];
        }

        if (array_key_exists('h', $result)) {
            $result['height'] = $result['h'];
        }

        if (isset($result['w']) && ($result['w'] < 32 || $result['w'] > 2000)) {
            throw new InvalidArgumentException('Width must be between 32 and 2000.');
        }

        if (isset($result['h']) && ($result['h'] < 32 || $result['h'] > 2000)) {
            throw new InvalidArgumentException('Height must be between 32 and 2000.');
        }

        if (isset($result['f']) && ! in_array($result['f'], ['webp', 'jpg', 'jpeg', 'png', 'avif'], true)) {
            throw new InvalidArgumentException('Format must be one of: webp, jpg, png, avif.');
        }

        if (isset($result['q']) && ($result['q'] < 1 || $result['q'] > 100)) {
            throw new InvalidArgumentException('Quality must be between 1 and 100.');
        }

        if (! isset($result['w']) && ! isset($result['h']) && ! isset($result['f']) && ! isset($result['q'])) {
            throw new InvalidArgumentException('At least one modifier (w, h, f, q) must be provided.');
        }

        return $result;
    }

    private static function parseInt(string $value): ?int
    {
        $int = filter_var(trim($value), FILTER_VALIDATE_INT);

        return $int === false ? null : (int) $int;
    }

    /**
     * @param  array<string, mixed>  $modifiers
     */
    public static function toCacheKey(array $modifiers, string $version): string
    {
        ksort($modifiers);

        return hash('xxh128', $version.'|'.json_encode($modifiers));
    }
}
