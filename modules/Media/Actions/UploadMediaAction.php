<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use App\Enums\MediaVisibilityEnum;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;

/**
 * Action for uploading a new media file.
 *
 * Stores the uploaded file on the disk matching the requested
 * visibility and persists the resulting record.
 */
final readonly class UploadMediaAction
{
    /**
     * Execute the upload media action.
     *
     * @param  MediaUploadPayload  $payload  The payload.
     * @return Media The newly created media instance.
     */
    public function handle(MediaUploadPayload $payload): Media
    {
        $disk = $payload->visibility === MediaVisibilityEnum::Private ? 'local' : 'public';

        $path = $payload->file->store('media/'.now()->format('Y/m'), $disk);

        return Media::create([
            'disk' => $disk,
            'mime_type' => (string) $payload->file->getMimeType(),
            'size' => (int) $payload->file->getSize(),
            'path' => $path,
            'meta' => [
                'original_name' => $payload->file->getClientOriginalName(),
                'extension' => strtolower($payload->file->getClientOriginalExtension()),
            ],
            'uploaded_by' => $payload->uploadedBy,
        ]);
    }
}
