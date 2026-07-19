<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\IAM\Models\User;

/**
 * @property-read User $resource
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    use FormatDate;

    /**
     * @return array{id: string, name: ?string, email: ?string, avatar: ?string, roles: string[]|null, permissions: string[]|null, email_verified_at: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        /** @var list<string>|null $roles */
        $roles = $this->resource->relationLoaded('roles')
            ? $this->resource->roles->pluck('name')->values()->all()
            : null;

        /** @var list<string>|null $permissions */
        $permissions = ($this->resource->relationLoaded('roles') || $this->resource->relationLoaded('permissions'))
            ? $this->resource->getAllPermissions()->pluck('name')->values()->all()
            : null;

        return [
            'id' => $this->resource->id,
            'name' => array_key_exists('name', $attributes) ? $this->resource->name : null,
            'email' => array_key_exists('email', $attributes) ? $this->resource->email : null,
            'avatar' => array_key_exists('avatar', $attributes) ? $this->resource->avatar : null,
            'roles' => $roles,
            'permissions' => $permissions,
            'email_verified_at' => array_key_exists('email_verified_at', $attributes) ? $this->formatDate($this->resource->email_verified_at) : null,
            'created_at' => array_key_exists('created_at', $attributes) ? $this->formatDate($this->resource->created_at) : null,
            'updated_at' => array_key_exists('updated_at', $attributes) ? $this->formatDate($this->resource->updated_at) : null,
            'deleted_at' => array_key_exists('deleted_at', $attributes) ? $this->formatDate($this->resource->deleted_at) : null,
        ];
    }
}
