<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Modules\IAM\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @property-read Role $resource
 *
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    use FormatDate;

    /**
     * @return string[]|MissingValue
     */
    private function getPermissions(): array|MissingValue
    {
        if (! $this->resource->relationLoaded('permissions')) {
            return new MissingValue;
        }

        return $this->resource->permissions->map(fn (Permission $permission): string => $permission->name)->values()->all();
    }

    /**
     * @return array{id: string, name: string, description: ?string, permissions: string[]|MissingValue, created_at: ?string, updated_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => array_key_exists('description', $attributes)
                ? $this->resource->description
                : null,
            'permissions' => $this->getPermissions(),
            'created_at' => array_key_exists('created_at', $attributes)
                ? $this->formatDate($this->resource->created_at)
                : null,
            'updated_at' => array_key_exists('updated_at', $attributes)
                ? $this->formatDate($this->resource->updated_at)
                : null,
        ];
    }
}
