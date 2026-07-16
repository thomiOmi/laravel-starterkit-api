<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDate;
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
    use FormatDate;

    /**
     * @return array{id: string, name: string, description: ?string, permissions: string[]|null, created_at: ?string, updated_at: ?string}
     */
    public function toArray(Request $request): array
    {
        /** @var list<string>|null $permissions */
        $permissions = $this->resource->relationLoaded('permissions')
            ? $this->resource->permissions->pluck('name')->values()->all()
            : null;

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'permissions' => $permissions,
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
        ];
    }
}
