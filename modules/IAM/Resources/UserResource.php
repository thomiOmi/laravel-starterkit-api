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
     * @return array{id: string, name: string, email: string, avatar: ?string, roles: ?string[], permissions: ?string[], email_verified_at: ?string, created_at: string, updated_at: string, deleted_at: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'avatar' => $this->resource->avatar,
            'roles' => $this->whenLoaded('roles', fn () => $this->resource->roles->pluck('name')),
            'permissions' => $this->when($this->relationLoaded('roles') || $this->relationLoaded('permissions'), fn () => $this->resource->getAllPermissions()->pluck('name')),
            'email_verified_at' => $this->formatDate($this->resource->email_verified_at),
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
            'deleted_at' => $this->formatDate($this->resource->deleted_at),
        ];
    }
}
