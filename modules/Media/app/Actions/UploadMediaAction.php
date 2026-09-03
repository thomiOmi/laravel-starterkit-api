<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use App\Enums\MediaCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Enums\MediaVisibilityEnum;
use Modules\Media\Events\MediaCreated;
use Modules\Media\Events\MediaUploaded;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;
use Modules\Media\Services\MediaConversionService;
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

        if ($modelConversions !== null) {
            // Model defines conversions, use service with model context
            if (config()->boolean('media.queue', false)) {
                ProcessMediaJob::dispatch($media->id);

                return;
            }

            try {
                // For model-driven, generate via service but respect performOnCollections
                app(MediaConversionService::class)->generate($media);
            } catch (Throwable) {
                // Ignore
            }

            return;
        }

        /** @var array<string, mixed> $conversions */
        $conversions = config()->array('media.conversions', []);

        if ($conversions === []) {
            return;
        }

        if (config()->boolean('media.queue', false)) {
            ProcessMediaJob::dispatch($media->id);

            return;
        }

        try {
            app(MediaConversionService::class)->generate($media);
        } catch (Throwable) {
            // Ignore conversion failures during upload.
        }
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeProcessedImage(MediaUploadPayload $payload, Model $owner, ?Model $uploader = null): array
    {
        $disk = config()->string('media.disk', 'public');
        $visibility = $this->resolveVisibility($payload->collectionName, $owner);
        $image = Image::fromUpload($payload->file)->orient()->optimize();

        $fullPath = $image->store($payload->collectionName, $disk, ['visibility' => $visibility->value]);

        if ($fullPath === false) {
            throw new ImageException('The processed image could not be stored.');
        }

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
        $filename = $file->hashName();
        $fullPath = $payload->collectionName.'/'.$filename;

        Storage::disk($disk)->putFileAs($payload->collectionName, $file, $filename, ['visibility' => $visibility->value]);

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

        $visibility = config()->string('media.collections.'.$collectionName.'.visibility');

        if ($visibility === MediaVisibilityEnum::Public->value) {
            return MediaVisibilityEnum::Public;
        }

        if ($visibility === MediaVisibilityEnum::Private->value) {
            return MediaVisibilityEnum::Private;
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

        return config()->boolean('media.collections.'.$collectionName.'.single_file', false);
    }

    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws Throwable When the media row cannot be persisted.
     */
    private function persistRow(MediaUploadPayload $payload, Model $owner, ?Model $uploader, string $disk, string $fullPath, string $mimeType, int $size, array $meta): Media
    {
        $isSingle = $this->isSingleFileCollection($payload->collectionName, $owner);

        try {
            $media = DB::transaction(function () use ($payload, $owner, $uploader, $disk, $fullPath, $mimeType, $size, $meta, $isSingle): Media {
                $file = $payload->file;

                if ($isSingle) {
                    $existing = Media::query()
                        ->where('model_type', $owner->getMorphClass())
                        ->where('model_id', $owner->getKey())
                        ->where('collection_name', $payload->collectionName)
                        ->lockForUpdate()
                        ->first();

                    if ($existing !== null) {
                        $existing->fill([
                            'disk' => $disk,
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'path' => $fullPath,
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

                $media = Media::query()->create([
                    'collection_name' => $payload->collectionName,
                    'disk' => $disk,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'path' => $fullPath,
                    'visibility' => $this->resolveVisibility($payload->collectionName, $owner)->value,
                    'original_name' => $file->getClientOriginalName(),
                    'original_extension' => $file->getClientOriginalExtension(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
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
            if ($isSingle && $media->wasChanged('path')) {
                $oldPath = $media->getOriginal('path');

                if (is_string($oldPath) && $oldPath !== $media->path) {
                    Storage::disk($disk)->delete($oldPath);
                    Storage::disk($disk)->deleteDirectory('variants/'.$media->id);
                }
            }

            return $media;
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($fullPath);

            throw $exception;
        }
    }
}
