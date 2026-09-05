<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use App\Enums\MediaCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Enums\MediaVisibilityEnum;
use Modules\Media\Events\MediaCreated;
use Modules\Media\Events\MediaUploaded;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;
use Modules\Media\Services\MediaConversionService;
use Modules\Media\Support\DisallowedExtensions;
use Modules\Media\Support\FileNamer\MediaFileNamer;
use Modules\Media\Support\MediaPrefix;
use Modules\Media\Support\StorageOptions;
use Throwable;

/**
 * Store an uploaded file on the configured disk and persist a Media row.
 *
 * Decodable images are normalized through the first-party image pipeline:
 * EXIF orientation is applied and the file is re-encoded as WebP, so the
 * persisted mime_type and size always describe the processed file. Files
 * outside the processable set are stored untouched.
 */
final readonly class UploadMediaAction
{
    /**
     * MIME types the GD/Imagick pipeline is able to decode.
     */
    private const array PROCESSABLE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];

    /**
     * @return array{media: Media, url: string|null}
     *
     * @throws Throwable When the media row cannot be persisted; the stored
     *                   file is removed again so no orphan is left behind.
     */
    public function handle(MediaUploadPayload $payload, Model $owner, ?Model $uploader = null): array
    {
        $this->guardFileName($payload->file->getClientOriginalName());
        $this->guardCollectionAcceptance($payload, $owner);

        if ($payload->preservingOriginal) {
            return $this->dispatchUploaded($this->storeRaw($payload, $owner, $uploader));
        }

        if (! in_array((string) $payload->file->getMimeType(), self::PROCESSABLE_MIMES, true)) {
            return $this->dispatchUploaded($this->storeRaw($payload, $owner, $uploader));
        }

        try {
            return $this->dispatchUploaded($this->storeProcessedImage($payload, $owner, $uploader));
        } catch (ImageException) {
            // Undecodable bytes that still passed extension validation are
            // stored untouched rather than failing the whole upload.
            return $this->dispatchUploaded($this->storeRaw($payload, $owner, $uploader));
        }
    }

    /**
     * Announce a finished upload to listeners.
     *
     * @param  array{media: Media, url: string|null}  $result
     * @return array{media: Media, url: string|null}
     */
    private function dispatchUploaded(array $result): array
    {
        event(new MediaUploaded($result['media']));
        event(new MediaCreated($result['media']));

        $this->dispatchConversions($result['media']);

        return $result;
    }

    private function dispatchConversions(Media $media): void
    {
        if (! str_starts_with($media->mime_type, 'image/')) {
            return;
        }

        // Check model-driven conversions first
        $modelConversions = null;

        if ($media->model_type !== null && $media->model_id !== null) {
            $modelClass = $media->model_type;

            if ($modelClass !== '' && class_exists($modelClass) && is_a($modelClass, Model::class, true)) {
                /** @var class-string<Model> $modelClass */
                $model = $modelClass::query()->whereKey($media->model_id)->first();

                if ($model instanceof HasMedia) {
                    $conversions = $model->getMediaConversions($media);

                    if ($conversions !== []) {
                        $modelConversions = $conversions;
                    }
                }
            }
        }

        $wantsResponsive = GenerateResponsiveImagesAction::wantsResponsive($media);

        if ($modelConversions !== null || $wantsResponsive) {
            if (config()->boolean('media.queue', false)) {
                ProcessMediaJob::dispatch($media->id);

                return;
            }

            if ($modelConversions !== null) {
                try {
                    // For model-driven, generate via service but respect performOnCollections
                    app(MediaConversionService::class)->generate($media);
                } catch (Throwable) {
                    // Ignore
                }
            }

            if ($wantsResponsive) {
                try {
                    app(GenerateResponsiveImagesAction::class)->handle($media);
                } catch (Throwable) {
                    // Ignore
                }
            }

            return;
        }

        // No model-driven conversions and no responsive flag — nothing to generate.
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeProcessedImage(MediaUploadPayload $payload, Model $owner, ?Model $uploader = null): array
    {
        $disk = config()->string('media.disk', 'public');
        $visibility = $this->resolveVisibility($payload->collectionName, $owner);
        $image = Image::fromUpload($payload->file)->orient()->optimize();

        $storedPath = $image->store(MediaPrefix::directory($payload->collectionName), $disk, StorageOptions::forVisibility($visibility->value));

        if ($storedPath === false) {
            throw new ImageException('The processed image could not be stored.');
        }

        $fullPath = $this->applyFileNamer($storedPath, $disk);

        /** @var array<string, mixed> $meta */
        $meta = array_filter([
            'original_name' => $payload->file->getClientOriginalName(),
            'width' => $image->width(),
            'height' => $image->height(),
        ]);

        $media = $this->persistRow(
            payload: $payload,
            owner: $owner,
            uploader: $uploader,
            disk: $disk,
            fullPath: $fullPath,
            mimeType: $image->mimeType(),
            size: (int) Storage::disk($disk)->size($fullPath),
            meta: $meta,
        );

        return ['media' => $media, 'url' => $media->url()];
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeRaw(MediaUploadPayload $payload, Model $owner, ?Model $uploader = null): array
    {
        $disk = config()->string('media.disk', 'public');
        $visibility = $this->resolveVisibility($payload->collectionName, $owner);
        $file = $payload->file;
        $filename = app(MediaFileNamer::class)->originalFileName($file->hashName());
        $fullPath = MediaPrefix::basePath($payload->collectionName, $filename);

        Storage::disk($disk)->putFileAs(MediaPrefix::directory($payload->collectionName), $file, $filename, StorageOptions::forVisibility($visibility->value));

        $media = $this->persistRow(
            payload: $payload,
            owner: $owner,
            uploader: $uploader,
            disk: $disk,
            fullPath: $fullPath,
            mimeType: (string) $file->getMimeType(),
            size: (int) $file->getSize(),
            meta: ['original_name' => $file->getClientOriginalName()],
        );

        return ['media' => $media, 'url' => $media->url()];
    }

    /**
     * Rename a just-stored file through the configured file namer.
     *
     * Explicit per-call names (PendingMedia::usingFileName/sanitizer) already
     * rebuilt the UploadedFile before this action runs, so the namer only sees
     * the final candidate name. The default namer is identity: no move happens.
     */
    private function applyFileNamer(string $storedPath, string $disk): string
    {
        $candidate = app(MediaFileNamer::class)->originalFileName(basename($storedPath));

        if ($candidate === '' || $candidate === basename($storedPath)) {
            return $storedPath;
        }

        $target = dirname($storedPath).'/'.$candidate;

        Storage::disk($disk)->move($storedPath, $target);

        return $target;
    }

    /**
     * Reject executable file names and files the target collection refuses.
     *
     * Runs for every entry point (HTTP and programmatic), unlike the
     * FormRequest rules which only cover HTTP uploads.
     */
    private function guardFileName(string $filename): void
    {
        if (DisallowedExtensions::contains($filename)) {
            throw new InvalidArgumentException(__('validation.media_disallowed_extension'));
        }
    }

    private function guardCollectionAcceptance(MediaUploadPayload $payload, Model $owner): void
    {
        if (! $owner instanceof HasMedia) {
            return;
        }

        $collection = $owner->getMediaCollection($payload->collectionName);

        if ($collection === null) {
            return;
        }

        $mimeType = (string) $payload->file->getMimeType();

        if ($collection->acceptsMimeTypes !== [] && ! in_array($mimeType, $collection->acceptsMimeTypes, true)) {
            throw new InvalidArgumentException(__('validation.media_not_accepted'));
        }

        $acceptsFile = $collection->acceptsFile;

        if ($acceptsFile !== null && $acceptsFile($payload->file) !== true) {
            throw new InvalidArgumentException(__('validation.media_not_accepted'));
        }
    }

    private function resolveVisibility(string $collectionName, ?Model $owner = null): MediaVisibilityEnum
    {
        // Check model-registered collection first
        if ($owner instanceof HasMedia) {
            $collection = $owner->getMediaCollection($collectionName);

            if ($collection !== null && $collection->visibility !== null) {
                return $collection->visibility === MediaVisibilityEnum::Public->value
                    ? MediaVisibilityEnum::Public
                    : MediaVisibilityEnum::Private;
            }
        }

        // Fallback to legacy hard-coded rule for avatars.
        return $collectionName === MediaCollection::Avatars->value
            ? MediaVisibilityEnum::Public
            : MediaVisibilityEnum::Private;
    }

    private function isSingleFileCollection(string $collectionName, ?Model $owner = null): bool
    {
        if ($owner instanceof HasMedia) {
            $collection = $owner->getMediaCollection($collectionName);

            if ($collection !== null) {
                return $collection->singleFile;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws Throwable When the media row cannot be persisted.
     */
    private function persistRow(MediaUploadPayload $payload, Model $owner, ?Model $uploader, string $disk, string $fullPath, string $mimeType, int $size, array $meta): Media
    {
        $isSingle = $this->isSingleFileCollection($payload->collectionName, $owner);
        $conversionsDisk = config('media.conversions_disk_name');
        $conversionsDisk = is_string($conversionsDisk) && $conversionsDisk !== '' ? $conversionsDisk : $disk;

        try {
            // A custom file namer runs after the client-name guard, so the
            // final stored basename is checked again; the catch below
            // removes the stored file when it is rejected here.
            $this->guardFileName(basename($fullPath));
            // Capture the replaced file name before save: getOriginal() is
            // synced on save, so it cannot be trusted for cleanup afterwards.
            $replacedFileName = null;
            $media = DB::transaction(function () use ($payload, $owner, $uploader, $disk, $conversionsDisk, $fullPath, $mimeType, $size, $meta, $isSingle, &$replacedFileName): Media {
                $file = $payload->file;

                if ($isSingle) {
                    $existing = Media::query()
                        ->where('model_type', $owner->getMorphClass())
                        ->where('model_id', $owner->getKey())
                        ->where('collection_name', $payload->collectionName)
                        ->lockForUpdate()
                        ->first();

                    if ($existing !== null) {
                        $replacedFileName = $existing->file_name;
                        $fileName = basename($fullPath);
                        $name = pathinfo($fileName, PATHINFO_FILENAME);

                        $existing->fill([
                            'name' => $name,
                            'file_name' => $fileName,
                            'disk' => $disk,
                            'conversions_disk' => $conversionsDisk,
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'visibility' => $this->resolveVisibility($payload->collectionName, $owner)->value,
                            'original_name' => $file->getClientOriginalName(),
                            'original_extension' => $file->getClientOriginalExtension(),
                            'sha256' => hash_file('sha256', $file->getRealPath()),
                            'meta' => $meta,
                            'custom_properties' => $existing->custom_properties,
                            'order_column' => $existing->order_column,
                        ]);

                        if ($uploader !== null) {
                            $existing->uploadedBy()->associate($uploader);
                        }

                        $existing->save();

                        return $existing;
                    }
                }

                $nextOrder = 1;

                if (! $isSingle) {
                    $maxOrder = Media::query()
                        ->where('model_type', $owner->getMorphClass())
                        ->where('model_id', $owner->getKey())
                        ->where('collection_name', $payload->collectionName)
                        ->max('order_column');

                    $nextOrder = is_numeric($maxOrder) ? ((int) $maxOrder + 1) : 1;
                }

                $fileName = basename($fullPath);
                $name = pathinfo($fileName, PATHINFO_FILENAME);

                $media = Media::query()->create([
                    'collection_name' => $payload->collectionName,
                    'name' => $name,
                    'file_name' => $fileName,
                    'disk' => $disk,
                    'conversions_disk' => $conversionsDisk,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'visibility' => $this->resolveVisibility($payload->collectionName, $owner)->value,
                    'original_name' => $file->getClientOriginalName(),
                    'original_extension' => $file->getClientOriginalExtension(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'manipulations' => [],
                    'generated_conversions' => [],
                    'responsive_images' => [],
                    'meta' => $meta,
                    'custom_properties' => null,
                    'order_column' => $nextOrder,
                ]);

                $media->model()->associate($owner);

                if ($uploader !== null) {
                    $media->uploadedBy()->associate($uploader);
                }

                $media->save();

                return $media;
            });

            // Clean up old file and its variants after successful single_file replacement.
            if ($isSingle && $media->wasChanged('file_name') && is_string($replacedFileName) && $replacedFileName !== $media->file_name) {
                Storage::disk($disk)->delete(MediaPrefix::basePath($media->collection_name, $replacedFileName));
                Storage::disk($disk)->deleteDirectory(MediaPrefix::join('variants', (string) $media->id));
            }

            return $media;
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($fullPath);

            throw $exception;
        }
    }
}
