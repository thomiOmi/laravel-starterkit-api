<?php

declare(strict_types=1);

namespace Modules\Tenant\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenant;

/**
 * @mixin Tenant
 */
class TenantResource extends BaseResource
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
            'domains' => $this->whenLoaded('domains', fn () => $this->domains->pluck('domain')),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
