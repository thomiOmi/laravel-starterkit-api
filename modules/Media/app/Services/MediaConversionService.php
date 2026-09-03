<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaConversion;
use Throwable;

/**
 * Generates configured image conversions for a media item.
 */
final readonly class MediaConversionService
{
    /**
     * Generate all configured conversions for the given media.
     *
     * @return array<string, MediaConversion>
     */
    public function generate(Media $media): array
    {
        if (! str_starts_with($media->mime_type, 'image/')) {
            return [];
        }

        /** @var array<string, array{width?: int, height?: int, fit?: string, format?: string, quality?: int}> $conversions */
        $conversions = config()->array('media.conversions', []);

        if ($conversions === []) {
            return [];
        }

        $results = [];

        foreach ($conversions as $name => $cfg) {
            try {
                $results[$name] = $this->generateOne($media, $name, $cfg);
            } catch (Throwable) {
                // Skip failed conversions, continue with others.
                continue;
            }
        }

        return $results;
    }

    /**
     * Generate a single conversion.
     *
     * @param  array{width?: int, height?: int, fit?: string, format?: string, quality?: int}  $cfg
     */
    public function generateOne(Media $media, string $name, array $cfg): MediaConversion
    {
        $disk = $media->disk;
        $width = array_key_exists('width', $cfg) ? $cfg['width'] : null;
        $height = array_key_exists('height', $cfg) ? $cfg['height'] : null;
        $format = $cfg['format'] ?? 'webp';
        $quality = $cfg['quality'] ?? 80;
        $fit = $cfg['fit'] ?? 'contain';

        if (is_int($width) && $width < 1) {
            $width = 1;
        }

        if (is_int($height) && $height < 1) {
            $height = 1;
        }

        if ($quality < 1) {
            $quality = 1;
        }

        if ($quality > 100) {
            $quality = 100;
        }

        $path = $media->getPath();

        if (! is_string($path)) {
            throw new \RuntimeException('Media path is missing.');
        }

        $image = Image::fromStorage($path, $disk);

        if ($width !== null || $height !== null) {
            if ($fit === 'cover' && $width !== null && $height !== null) {
                $image->cover(width: $width, height: $height);
            } else {
                $image->scale(width: $width, height: $height);
            }
        }

        $image->toFormat($format)->quality($quality);

        $ext = $format === 'jpg' ? 'jpg' : $format;
        $conversionPath = 'conversions/'.$media->id.'/'.$name.'.'.$ext;

        // Store conversion file.
        $image->storeAs(dirname($conversionPath), basename($conversionPath), $disk, ['visibility' => $media->visibility->value]);

        $mime = $image->mimeType();
        $size = null;

        try {
            $size = (int) Storage::disk($disk)->size($conversionPath);
        } catch (Throwable) {
            $size = null;
        }

        $etag = hash('xxh128', $media->id.'|'.$name.'|'.$media->updated_at?->timestamp.'|'.$format.'|'.$width.'x'.$height);

        return $media->conversions()->updateOrCreate(
            ['name' => $name],
            [
                'disk' => $disk,
                'path' => $conversionPath,
                'mime_type' => $mime,
                'size' => $size,
                'etag' => $etag,
            ]
        );
    }

    /**
     * Delete all conversions for the media.
     */
    public function deleteConversions(Media $media): void
    {
        foreach ($media->conversions()->get() as $conv) {
            Storage::disk($conv->disk)->delete($conv->path);
            $conv->delete();
        }

        Storage::disk($media->disk)->deleteDirectory('conversions/'.$media->id);
    }
}
