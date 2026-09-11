<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaPrefix;
use Modules\Media\Support\StorageOptions;

/**
 * Generate an on-demand image conversion for the given media (Spatie pattern).
 *
 * The controller remains responsible for HTTP concerns (ETag,
 * If-None-Match, cache headers, derived-path derivation), while this
 * action owns the image pipeline: loading the source, applying
 * scale/cover/contain, format conversion and persisting the derived conversion.
 */
final readonly class GenerateOnDemandConversionAction
{
    /**
     * Build the readable derived conversion path for the given parsed modifiers.
     *
     * @param  array<string, mixed>  $parsed
     */
    public function buildConversionPath(Media $media, array $parsed, string $cacheKey, string $format): string
    {
        /** @var int<1, 2000>|null $width */
        $width = isset($parsed['w']) && is_int($parsed['w']) ? $parsed['w'] : null;
        /** @var int<1, 2000>|null $height */
        $height = isset($parsed['h']) && is_int($parsed['h']) ? $parsed['h'] : null;
        /** @var string|null $fit */
        $fit = isset($parsed['fit']) && is_string($parsed['fit']) ? $parsed['fit'] : null;
        /** @var string|null $kernel */
        $kernel = isset($parsed['kernel']) && is_string($parsed['kernel']) ? $parsed['kernel'] : null;
        /** @var int<1, 100> $quality */
        $quality = isset($parsed['q']) && is_int($parsed['q']) ? $parsed['q'] : 80;

        $ext = $format === 'jpg' ? 'jpg' : $format;
        $readableParts = [];

        if ($width !== null) {
            $readableParts[] = "w{$width}";
        }

        if ($height !== null) {
            $readableParts[] = "h{$height}";
        }

        if ($format !== '') {
            $readableParts[] = "f_{$format}";
        }

        if ($quality !== 80) {
            $readableParts[] = "q{$quality}";
        }

        if ($fit !== null) {
            $readableParts[] = "fit_{$fit}";
        }

        if ($kernel !== null) {
            $readableParts[] = "kernel_{$kernel}";
        }

        $readable = implode('-', $readableParts);

        if ($readable === '') {
            $readable = 'original';
        }

        return MediaPrefix::join('conversions/derived', (string) $media->id, $readable.'-'.substr($cacheKey, 0, 8).'.'.$ext);
    }

    /**
     * Generate and persist the derived conversion file.
     *
     * @param  array<string, mixed>  $parsed
     */
    public function handle(Media $media, array $parsed, string $conversionPath): void
    {
        $path = $media->getPath();

        abort_unless(is_string($path) && Storage::disk($media->disk)->exists($path), 404, __('general.resource_not_found', ['resource' => 'File']));

        /** @var int<1, 2000>|null $width */
        $width = isset($parsed['w']) && is_int($parsed['w']) ? $parsed['w'] : null;
        /** @var int<1, 2000>|null $height */
        $height = isset($parsed['h']) && is_int($parsed['h']) ? $parsed['h'] : null;
        /** @var string $format */
        $format = isset($parsed['f']) && is_string($parsed['f']) ? $parsed['f'] : 'webp';
        /** @var string|null $fit */
        $fit = isset($parsed['fit']) && is_string($parsed['fit']) ? $parsed['fit'] : null;
        /** @var int<1, 100> $quality */
        $quality = isset($parsed['q']) && is_int($parsed['q']) ? $parsed['q'] : 80;

        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        $image = Image::fromStorage($path, $media->disk);

        if ($width !== null || $height !== null) {
            if ($fit === 'cover' && $width !== null && $height !== null) {
                $image = $image->cover(width: $width, height: $height);
            } elseif ($fit === 'contain' && $width !== null && $height !== null) {
                $image = $image->contain(width: $width, height: $height);
            } elseif ($fit === 'fill' && $width !== null && $height !== null) {
                $image = $image->resize(width: $width, height: $height);
            } elseif (isset($parsed['s']) && $width !== null && $height !== null) {
                $image = $image->cover(width: $width, height: $height);
            } else {
                $image = $image->scale(width: $width, height: $height);
            }
        }

        $image = $image->toFormat($format)->quality($quality);
        $image->storeAs(dirname($conversionPath), basename($conversionPath), $media->disk, StorageOptions::forVisibility($media->visibility->value));
    }
}
