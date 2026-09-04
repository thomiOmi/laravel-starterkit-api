<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileNamer\MediaFileNamer;
use Modules\Media\Support\StorageOptions;
use Throwable;

/**
 * Generate width-capped responsive siblings for an image media item.
 *
 * Widths come from the media.responsive.widths config; anything larger
 * than the original file is skipped so images are never upscaled.
 * Results are persisted on the media responsive_images JSON as
 * {width: {path, size}}; URLs are built on read via Media::getSrcset().
 */
final readonly class GenerateResponsiveImagesAction
{
    /**
     * Determine whether the owning collection opted into responsive images.
     */
    public static function wantsResponsive(Media $media): bool
    {
        if (! str_starts_with($media->mime_type, 'image/')) {
            return false;
        }

        if ($media->model_type === null || $media->model_id === null) {
            return false;
        }

        $modelClass = $media->model_type;

        if ($modelClass === '' || ! class_exists($modelClass) || ! is_a($modelClass, Model::class, true)) {
            return false;
        }

        /** @var class-string<Model> $modelClass */
        $model = $modelClass::query()->whereKey($media->model_id)->first();

        if (! $model instanceof HasMedia) {
            return false;
        }

        $collection = $model->getMediaCollection($media->collection_name);

        return $collection !== null && $collection->generateResponsiveImages;
    }

    /**
     * @return array<int, array{path: string, size: int|null}>
     */
    public function handle(Media $media): array
    {
        if (! str_starts_with($media->mime_type, 'image/')) {
            return [];
        }

        $path = $media->getPath();

        if (! is_string($path)) {
            return [];
        }

        $disk = $media->conversions_disk ?? $media->disk;

        try {
            $sourceWidth = Image::fromStorage($path, $disk)->width();
        } catch (Throwable) {
            return [];
        }

        $widths = $this->targetWidths($sourceWidth);

        if ($widths === []) {
            return [];
        }

        $results = [];

        foreach ($widths as $width) {
            try {
                $results[$width] = $this->generateOne($media, $disk, $path, $width);
            } catch (Throwable) {
                continue;
            }
        }

        if ($results !== []) {
            $media->responsive_images = $results;
            $media->save();
        }

        return $results;
    }

    /**
     * @return array<int, int>
     */
    private function targetWidths(int $sourceWidth): array
    {
        $widths = [];

        foreach (config()->array('media.responsive.widths', []) as $width) {
            if (is_int($width) && $width > 0 && $width <= $sourceWidth) {
                $widths[] = $width;
            }
        }

        $widths = array_values(array_unique($widths));
        sort($widths);

        return $widths;
    }

    /**
     * @return array{path: string, size: int|null}
     */
    private function generateOne(Media $media, string $disk, string $path, int $width): array
    {
        $width = max(1, $width);
        $format = $this->responsiveFormat($media);

        $image = Image::fromStorage($path, $disk)->scale(width: $width)->toFormat($format)->quality(80);

        $fileName = $width.'-'.app(MediaFileNamer::class)->responsiveFileName($media->file_name);
        $directory = dirname($path).'/responsive-images';

        $image->storeAs($directory, $fileName, $disk, StorageOptions::forVisibility($media->visibility->value));

        $responsivePath = $directory.'/'.$fileName;

        try {
            $size = (int) Storage::disk($disk)->size($responsivePath);
        } catch (Throwable) {
            $size = null;
        }

        return ['path' => $responsivePath, 'size' => $size];
    }

    private function responsiveFormat(Media $media): string
    {
        $extension = strtolower($media->original_extension ?? pathinfo($media->file_name, PATHINFO_EXTENSION));

        if ($extension === 'jpeg') {
            return 'jpg';
        }

        if (in_array($extension, ['jpg', 'png', 'webp'], true)) {
            return $extension;
        }

        return 'webp';
    }
}
