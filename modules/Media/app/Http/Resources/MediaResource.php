<?php

declare(strict_types=1);

namespace Modules\Media\Http\Resources;

use App\Concerns\FormatDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

/**
 * @property Media $resource
 *
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    use FormatDate;

    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, collection_name: string, mime_type: string, size: int, visibility: string, url: string, original_name: string|null, uploaded_by: string|null, created_at: ?string}
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        /** @var Media $media */
        $media = $this->resource;

        return [
            'id' => $media->id,
            'collection_name' => $media->collection_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'visibility' => $media->visibility->value,
            'url' => Storage::disk($media->disk)->url($media->path),
            'original_name' => is_array($media->meta) && is_string($media->meta['original_name'] ?? null)
                ? $media->meta['original_name']
                : null,
            'uploaded_by' => $media->uploaded_by,
            'created_at' => $this->formatDate($media->created_at),
        ];
    }
}
