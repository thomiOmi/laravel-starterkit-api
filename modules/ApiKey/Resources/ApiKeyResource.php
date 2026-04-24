<?php

declare(strict_types=1);

namespace Modules\ApiKey\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\ApiKey\Models\ApiKey;

/**
 * @property-read ApiKey $resource
 *
 * @mixin ApiKey
 */
class ApiKeyResource extends BaseResource
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
            'secret_prefix' => $this->resource->secret_prefix,
            'abilities' => $this->resource->abilities,
            'ip_whitelist' => $this->resource->ip_whitelist,
            'last_used_at' => $this->formatDate($this->resource->last_used_at),
            'expires_at' => $this->formatDate($this->resource->expires_at),
            'created_at' => $this->formatDate($this->resource->created_at),
        ];
    }
}
