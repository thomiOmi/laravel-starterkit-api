<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDates;
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
    use FormatDates;

    /**
     * @return array{id: string, name: string, email: string, avatar: ?string, roles: string[]|null, permissions: string[]|null, email_verified_at: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string}
     */
    public function toArray(Request $request): array
    {
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
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'avatar' => $this->resource->avatar,
            'roles' => $roles,
            'permissions' => $permissions,
            'email_verified_at' => $this->formatDate($this->resource->email_verified_at),
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
            'deleted_at' => $this->formatDate($this->resource->deleted_at),
        ];
    }
}
