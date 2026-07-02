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
     * @return array{id: string, name: string, email: string, avatar: string|null, roles: string[]|null, permissions: string[]|null, email_verified_at: string|null, created_at: string, updated_at: string, deleted_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'avatar' => is_string($this->resource->avatar) ? $this->resource->avatar : null,
            'roles' => $this->resource->relationLoaded('roles')
                ? array_values($this->resource->roles->pluck('name')->map(fn (mixed $n) => (string) $n)->all())
                : null,
            'permissions' => ($this->resource->relationLoaded('roles') || $this->resource->relationLoaded('permissions'))
                ? array_values($this->resource->getAllPermissions()->pluck('name')->map(fn (mixed $n) => (string) $n)->all())
                : null,
            'email_verified_at' => $this->formatDate($this->resource->email_verified_at),
            'created_at' => (string) $this->formatDate($this->resource->created_at),
            'updated_at' => (string) $this->formatDate($this->resource->updated_at),
            'deleted_at' => $this->formatDate($this->resource->deleted_at),
        ];
    }
}
