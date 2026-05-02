<?php

declare(strict_types=1);

namespace Modules\Webhook\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\Webhook\Models\Webhook;

/**
 * @property-read Webhook $resource
 *
 * @mixin Webhook
 */
class WebhookResource extends BaseResource
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
            'url' => $this->resource->url,
            'events' => $this->resource->events,
            'is_active' => $this->resource->is_active,
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
        ];
    }
}
