<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use InvalidArgumentException;

/**
 * Builder for a single media conversion definition.
 *
 * Also parses on-the-fly modifiers (previously MediaModifier) via fromModifiers().
 */
final class MediaConversion
{
    public string $name;

    public ?int $width = null;

    public ?int $height = null;

    public string $fit = 'contain';

    public string $format = 'webp';

    public int $quality = 80;

    /** @var array<int, string> */
    public array $performOnCollections = [];

    public bool $nonQueued = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function height(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function fit(string $fit): self
    {
        $this->fit = $fit;

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function quality(int $quality): self
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * @param  array<int, string>|string  $collections
     */
    public function performOnCollections(array|string $collections): self
    {
        $this->performOnCollections = is_array($collections) ? $collections : [$collections];

        return $this;
    }

    public function nonQueued(): self
    {
        $this->nonQueued = true;

        return $this;
    }

    /**
     * Parse on-the-fly modifiers string into a conversion instance.
     *
     * Supports IPX style w_320,f_webp,q_80 and slash style s/320/f/webp.
     */
    public static function fromModifiers(string $modifiers, string $name = 'modifier'): self
    {
        $parsed = self::parseModifiers($modifiers);

        $instance = new self($name);

        if (isset($parsed['w'])) {
            $instance->width((int) $parsed['w']);
        }

        if (isset($parsed['h'])) {
            $instance->height((int) $parsed['h']);
        }

        if (isset($parsed['f']) || isset($parsed['format'])) {
            $instance->format(strtolower((string) ($parsed['f'] ?? $parsed['format'])));
        }

        if (isset($parsed['q'])) {
            $instance->quality((int) $parsed['q']);
        }

        return $instance;
    }

    /**
     * @return array{w?: int, h?: int, s?: string, f?: string, q?: int, format?: string, width?: int, height?: int}
     */
    public static function parse(string $modifiers): array
    {
        return self::parseModifiers($modifiers);
    }

    /**
     * @param  array<string, mixed>  $modifiers
     */
    public static function toCacheKey(array $modifiers, string $version): string
    {
        ksort($modifiers);

        return hash('xxh128', $version.'|'.json_encode($modifiers));
    }

    /**
     * @return array{w?: int, h?: int, s?: string, f?: string, q?: int, format?: string, width?: int, height?: int}
     */
    private static function parseModifiers(string $modifiers): array
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

        if (isset($result['f']) && ! in_array($result['f'], ['webp', 'jpg', 'jpeg'], true)) {
            throw new InvalidArgumentException('Format must be one of: webp, jpg.');
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
}
