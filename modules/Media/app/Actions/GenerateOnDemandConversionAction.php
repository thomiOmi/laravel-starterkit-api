<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
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
     * Generate and persist the derived conversion file.
     *
     * @param  array<string, mixed>  $parsed
     */
    public function handle(Media $media, array $parsed, string $conversionPath): void
    {
        $path = $media->getPath();
        $sourceDisk = Storage::disk($media->disk);

        if (! is_string($path) || ! $sourceDisk->exists($path)) {
            $mediaKey = $media->getKey();
            $notFoundKey = is_int($mediaKey) || is_string($mediaKey) ? $mediaKey : 0;

            throw (new ModelNotFoundException)->setModel(Media::class, $notFoundKey);
        }

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
        $conversionDisk = $media->conversions_disk ?? $media->disk;

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
        $image->storeAs(dirname($conversionPath), basename($conversionPath), $conversionDisk, StorageOptions::forVisibility($media->visibility->value));
    }
}
