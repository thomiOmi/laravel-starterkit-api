<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\IAM\Models\Role;

/**
 * @property-read Role $resource
 *
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    use FormatDates;

    /**
     * @return array{id: string, name: string, description: string|null, permissions: string[]|null, created_at: string, updated_at: string}
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, string>|null $permissions */
        $permissions = $this->resource->relationLoaded('permissions')
            ? array_values(array_map('strval', $this->resource->permissions->pluck('name')->all()))
            : null;

        return [
            'id' => (string) $this->resource->id,
            'name' => $this->resource->name,
            'description' => is_string($this->resource->description) ? $this->resource->description : null,
            'permissions' => $permissions,
            'created_at' => (string) $this->formatDate($this->resource->created_at),
            'updated_at' => (string) $this->formatDate($this->resource->updated_at),
        ];
    }
}
