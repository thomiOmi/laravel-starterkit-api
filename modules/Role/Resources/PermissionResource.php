<?php

declare(strict_types=1);

namespace Modules\Role\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\Role\Models\Permission;

/**
 * @property-read Permission $resource
 *
 * @mixin Permission
 */
class PermissionResource extends JsonResource
{
    protected function formatDate(\DateTimeInterface|string|null $date): ?string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date?->format('Y-m-d H:i:s');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'guard_name' => $this->resource->guard_name,
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
        ];
    }
}
