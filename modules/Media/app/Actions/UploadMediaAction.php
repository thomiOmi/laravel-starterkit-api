<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use App\Enums\MediaCollection;
use App\Enums\MediaVisibilityEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\User;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;
use Throwable;

/**
 * Store an uploaded file on the configured disk and persist a Media row.
 */
final readonly class UploadMediaAction
{
    /**
     * @return array{media: Media, url: string|null}
     *
     * @throws Throwable When the media row cannot be persisted; the stored
     *                   file is removed again so no orphan is left behind.
     */
    public function handle(MediaUploadPayload $payload, User $user): array
    {
        $disk = config()->string('media.disk', 'public');
        $file = $payload->file;
        $filename = $file->hashName();
        $fullPath = $payload->collectionName.'/'.$filename;

        Storage::disk($disk)->putFileAs($payload->collectionName, $file, $filename);

        try {
            $media = DB::transaction(fn (): Media => Media::query()->create([
                'collection_name' => $payload->collectionName,
                'disk' => $disk,
                'mime_type' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'path' => $fullPath,
                'visibility' => $payload->collectionName === MediaCollection::Avatars->value
                    ? MediaVisibilityEnum::Public->value
                    : MediaVisibilityEnum::Private->value,
                'meta' => ['original_name' => $file->getClientOriginalName()],
                'uploaded_by' => $user->id,
            ]));
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($fullPath);

            throw $exception;
        }

        return ['media' => $media, 'url' => Storage::disk($disk)->url($media->path)];
    }
}
