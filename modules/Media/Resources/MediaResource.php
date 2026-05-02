<?php

declare(strict_types=1);

namespace Modules\Media\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\Media\Models\Media;

/**
 * @property-read Media $resource
 *
 * @mixin Media
 */
class MediaResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'file_name' => $this->resource->file_name,
            'mime_type' => $this->resource->mime_type,
            'size' => $this->resource->size,
            'human_readable_size' => $this->resource->human_readable_size,
            'url' => $this->resource->getFullUrl(),
            'thumbnails' => [
                'thumb' => $this->resource->hasGeneratedConversion('thumb') ? $this->resource->getFullUrl('thumb') : null,
                'preview' => $this->resource->hasGeneratedConversion('preview') ? $this->resource->getFullUrl('preview') : null,
            ],
            'custom_properties' => $this->resource->custom_properties,
            'created_at' => $this->formatDate($this->resource->created_at),
        ];
    }
}
