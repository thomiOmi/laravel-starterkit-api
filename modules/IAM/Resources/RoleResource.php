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
     * @return array{id: string, name: string, description: string|null, permissions: string[]|null, created_at: string|null, updated_at: string|null}
     */
    public function toArray(Request $request): array
    {
        /** @var list<string>|null $permissions */
        $permissions = $this->resource->relationLoaded('permissions')
            ? $this->resource->permissions->pluck('name')->map(function (mixed $val): string {
                return (string) $val;
            })->values()->all()
            : null;

        return [
            'id' => (string) $this->resource->id,
            'name' => $this->resource->name,
            'description' => is_string($this->resource->description) ? $this->resource->description : null,
            'permissions' => $permissions,
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
        ];
    }
}
