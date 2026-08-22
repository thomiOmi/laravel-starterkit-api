<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\User;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;

/**
 * Store an uploaded file on the configured disk and persist a Media row.
 */
final readonly class UploadMediaAction
{
    /**
     * @return array{media: Media, url: string}
     */
    public function handle(MediaUploadPayload $payload, User $user): array
    {
        $disk = config()->string('media.disk', 'public');
        $file = $payload->file;
        $filename = $file->hashName();
        $fullPath = $payload->collectionName.'/'.$filename;

        Storage::disk($disk)->putFileAs($payload->collectionName, $file, $filename);

        $media = Media::query()->create([
            'collection_name' => $payload->collectionName,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'path' => $fullPath,
            'visibility' => 'private',
            'meta' => ['original_name' => $file->getClientOriginalName()],
            'uploaded_by' => $user->id,
        ]);

        return ['media' => $media, 'url' => Storage::disk($disk)->url($media->path)];
    }
}
