<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use App\Enums\MediaVisibilityEnum;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\User;
use Modules\Media\Events\MediaUploaded;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;
use Throwable;

final readonly class UploadMediaAction
{
    private const array PROCESSABLE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];

    /**
     * @return array{media: Media, url: string|null}
     *
     * @throws Throwable
     */
    public function handle(MediaUploadPayload $payload, User $user): array
    {
        $isSingle = $payload->collectionName === 'avatars';

        if ($isSingle) {
            if (! in_array((string) $payload->file->getMimeType(), self::PROCESSABLE_MIMES, true)) {
                return $this->dispatchUploaded($this->storeRawAvatars($payload, $user));
            }

            try {
                return $this->dispatchUploaded($this->storeProcessedAvatars($payload, $user));
            } catch (ImageException) {
                return $this->dispatchUploaded($this->storeRawAvatars($payload, $user));
            }
        }

        if (! in_array((string) $payload->file->getMimeType(), self::PROCESSABLE_MIMES, true)) {
            return $this->dispatchUploaded($this->storeRaw($payload, $user));
        }

        try {
            return $this->dispatchUploaded($this->storeProcessedImage($payload, $user));
        } catch (ImageException) {
            return $this->dispatchUploaded($this->storeRaw($payload, $user));
        }
    }

    /**
     * @param  array{media: Media, url: string|null}  $result
     * @return array{media: Media, url: string|null}
     */
    private function dispatchUploaded(array $result): array
    {
        /** @var Media $media */
        $media = $result['media'];

        event(new MediaUploaded($media));

        return $result;
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeProcessedImage(MediaUploadPayload $payload, User $user): array
    {
        $disk = config()->string('media.disk', 'public');
        $visibility = $this->resolveVisibility($payload->collectionName);
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
            user: $user,
            disk: $disk,
            fullPath: $fullPath,
            mimeType: $image->mimeType(),
            size: (int) Storage::disk($disk)->size($fullPath),
            meta: $meta,
        );

        return ['media' => $media, 'url' => Storage::disk($disk)->url($media->path)];
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeRaw(MediaUploadPayload $payload, User $user): array
    {
        $disk = config()->string('media.disk', 'public');
        $visibility = $this->resolveVisibility($payload->collectionName);
        $file = $payload->file;
        $filename = $file->hashName();
        $fullPath = $payload->collectionName.'/'.$filename;

        Storage::disk($disk)->putFileAs($payload->collectionName, $file, $filename, ['visibility' => $visibility->value]);

        $media = $this->persistRow(
            payload: $payload,
            user: $user,
            disk: $disk,
            fullPath: $fullPath,
            mimeType: (string) $file->getMimeType(),
            size: (int) $file->getSize(),
            meta: ['original_name' => $file->getClientOriginalName()],
        );

        return ['media' => $media, 'url' => Storage::disk($disk)->url($media->path)];
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeProcessedAvatars(MediaUploadPayload $payload, User $user): array
    {
        $disk = config()->string('media.disk', 'public');
        $visibility = $this->resolveVisibility($payload->collectionName);
        $image = Image::fromUpload($payload->file)->orient()->optimize();

        $newPath = $image->store($payload->collectionName, $disk, ['visibility' => $visibility->value]);

        if ($newPath === false) {
            throw new ImageException('The processed image could not be stored.');
        }

        /** @var array<string, mixed> $meta */
        $meta = array_filter([
            'original_name' => $payload->file->getClientOriginalName(),
            'width' => $image->width(),
            'height' => $image->height(),
        ]);

        $media = $this->persistAvatarsRow(
            payload: $payload,
            user: $user,
            disk: $disk,
            newPath: $newPath,
            mimeType: $image->mimeType(),
            size: (int) Storage::disk($disk)->size($newPath),
            meta: $meta,
        );

        return ['media' => $media, 'url' => Storage::disk($disk)->url($media->path)];
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeRawAvatars(MediaUploadPayload $payload, User $user): array
    {
        $disk = config()->string('media.disk', 'public');
        $visibility = $this->resolveVisibility($payload->collectionName);
        $file = $payload->file;
        $filename = $file->hashName();
        $newPath = $payload->collectionName.'/'.$filename;

        Storage::disk($disk)->putFileAs($payload->collectionName, $file, $filename, ['visibility' => $visibility->value]);

        $media = $this->persistAvatarsRow(
            payload: $payload,
            user: $user,
            disk: $disk,
            newPath: $newPath,
            mimeType: (string) $file->getMimeType(),
            size: (int) $file->getSize(),
            meta: ['original_name' => $file->getClientOriginalName()],
        );

        return ['media' => $media, 'url' => Storage::disk($disk)->url($media->path)];
    }

    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws Throwable
     */
    private function persistAvatarsRow(MediaUploadPayload $payload, User $user, string $disk, string $newPath, string $mimeType, int $size, array $meta): Media
    {
        $oldPath = null;
        $oldId = null;

        try {
            $media = DB::transaction(function () use ($payload, $user, $disk, $newPath, $mimeType, $size, $meta, &$oldPath, &$oldId): Media {
                $existing = Media::query()
                    ->where('uploaded_by', $user->id)
                    ->where('collection_name', 'avatars')
                    ->lockForUpdate()
                    ->first();

                $oldPath = $existing?->path;
                $oldId = $existing?->id;

                if ($existing !== null) {
                    $existing->update([
                        'disk' => $disk,
                        'mime_type' => $mimeType,
                        'size' => $size,
                        'path' => $newPath,
                        'visibility' => $this->resolveVisibility($payload->collectionName)->value,
                        'meta' => $meta,
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'order_column' => 1,
                    ]);

                    return $existing->refresh();
                }

                return Media::query()->create([
                    'collection_name' => $payload->collectionName,
                    'disk' => $disk,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'path' => $newPath,
                    'visibility' => $this->resolveVisibility($payload->collectionName)->value,
                    'meta' => $meta,
                    'uploaded_by' => $user->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'order_column' => 1,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($newPath);

            throw $exception;
        }

        if ($oldPath !== null && $oldPath !== $newPath) {
            Storage::disk($disk)->delete($oldPath);
        }

        if ($oldId !== null) {
            Storage::disk($disk)->deleteDirectory('variants/'.$oldId);
        }

        return $media;
    }

    private function resolveVisibility(string $collectionName): MediaVisibilityEnum
    {
        if ($collectionName === 'avatars') {
            return MediaVisibilityEnum::Public;
        }

        return MediaVisibilityEnum::Private;
    }

    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws Throwable When the media row cannot be persisted.
     */
    private function persistRow(MediaUploadPayload $payload, User $user, string $disk, string $fullPath, string $mimeType, int $size, array $meta): Media
    {
        try {
            return DB::transaction(function () use ($payload, $user, $disk, $fullPath, $mimeType, $size, $meta): Media {
                $maxOrder = Media::query()
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->where('collection_name', $payload->collectionName)
                    ->max('order_column');

                $orderColumn = is_int($maxOrder) ? $maxOrder + 1 : 1;

                return Media::query()->create([
                    'collection_name' => $payload->collectionName,
                    'disk' => $disk,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'path' => $fullPath,
                    'visibility' => $this->resolveVisibility($payload->collectionName)->value,
                    'meta' => $meta,
                    'uploaded_by' => $user->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'order_column' => $orderColumn,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($fullPath);

            throw $exception;
        }
    }
}
