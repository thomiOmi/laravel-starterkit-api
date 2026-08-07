<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Modules\IAM\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @property-read User $resource
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    use FormatDate;

    /**
     * @return string[]|MissingValue
     */
    private function getRoles(): array|MissingValue
    {
        if (! $this->resource->relationLoaded('roles')) {
            return new MissingValue;
        }

        return $this->resource->roles->map(fn (Role $role): string => $role->name)->values()->all();
    }

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
     * @return array{id: string, name: ?string, email: ?string, avatar: ?string, status: ?string, roles: string[]|MissingValue, permissions: string[]|MissingValue, email_verified_at: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->resource->id,
            'name' => array_key_exists('name', $attributes) ? $this->resource->name : null,
            'email' => array_key_exists('email', $attributes) ? $this->resource->email : null,
            'avatar' => array_key_exists('avatar', $attributes) ? $this->resource->avatar : null,
            'status' => array_key_exists('status', $attributes) ? $this->resource->status->value : null,
            'roles' => $this->getRoles(),
            'permissions' => $this->getPermissions(),
            'email_verified_at' => array_key_exists('email_verified_at', $attributes) ? $this->formatDate($this->resource->email_verified_at) : null,
            'created_at' => array_key_exists('created_at', $attributes) ? $this->formatDate($this->resource->created_at) : null,
            'updated_at' => array_key_exists('updated_at', $attributes) ? $this->formatDate($this->resource->updated_at) : null,
            'deleted_at' => array_key_exists('deleted_at', $attributes) ? $this->formatDate($this->resource->deleted_at) : null,
        ];
    }
}
