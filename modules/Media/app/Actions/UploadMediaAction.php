<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use App\Enums\MediaCollection;
use App\Enums\MediaVisibilityEnum;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\User;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;
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
    public function handle(MediaUploadPayload $payload, User $user): array
    {
        if (! in_array((string) $payload->file->getMimeType(), self::PROCESSABLE_MIMES, true)) {
            return $this->storeRaw($payload, $user);
        }

        try {
            return $this->storeProcessedImage($payload, $user);
        } catch (ImageException) {
            // Undecodable bytes that still passed extension validation are
            // stored untouched rather than failing the whole upload.
            return $this->storeRaw($payload, $user);
        }
    }

    /**
     * @return array{media: Media, url: string|null}
     */
    private function storeProcessedImage(MediaUploadPayload $payload, User $user): array
    {
        $disk = config()->string('media.disk', 'public');
        $image = Image::fromUpload($payload->file)->orient()->optimize();

        $fullPath = $image->store($payload->collectionName, $disk);

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
        $file = $payload->file;
        $filename = $file->hashName();
        $fullPath = $payload->collectionName.'/'.$filename;

        Storage::disk($disk)->putFileAs($payload->collectionName, $file, $filename);

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
     * @param  array<string, mixed>  $meta
     *
     * @throws Throwable When the media row cannot be persisted.
     */
    private function persistRow(MediaUploadPayload $payload, User $user, string $disk, string $fullPath, string $mimeType, int $size, array $meta): Media
    {
        try {
            return DB::transaction(fn (): Media => Media::query()->create([
                'collection_name' => $payload->collectionName,
                'disk' => $disk,
                'mime_type' => $mimeType,
                'size' => $size,
                'path' => $fullPath,
                'visibility' => $payload->collectionName === MediaCollection::Avatars->value
                    ? MediaVisibilityEnum::Public->value
                    : MediaVisibilityEnum::Private->value,
                'meta' => $meta,
                'uploaded_by' => $user->id,
            ]));
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($fullPath);

            throw $exception;
        }
    }
}
