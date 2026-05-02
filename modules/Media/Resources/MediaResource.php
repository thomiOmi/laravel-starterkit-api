<?php

declare(strict_types=1);

namespace Modules\Media\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $file_name
 * @property string $mime_type
 * @property int $size
 * @property string $human_readable_size
 * @property string $original_url
 * @property array $custom_properties
 * @property Carbon|null $created_at
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'url' => $this->getFullUrl(),
            'thumbnails' => [
                'thumb' => $this->hasGeneratedConversion('thumb') ? $this->getFullUrl('thumb') : null,
                'preview' => $this->hasGeneratedConversion('preview') ? $this->getFullUrl('preview') : null,
            ],
            'custom_properties' => $this->custom_properties,
            'created_at' => $this->created_at,
        ];
    }
}
