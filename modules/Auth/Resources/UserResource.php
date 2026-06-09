<?php

declare(strict_types=1);

namespace Modules\Auth\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\User\Models\User;

/**
 * @property-read User $resource
 *
 * @mixin User
 */
class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The incoming request.
     * @return array<string, mixed> The transformed resource array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'roles' => $this->whenLoaded('roles', fn () => $this->resource->roles->pluck('name')),
            'permissions' => $this->when(
                $this->relationLoaded('roles') || $this->relationLoaded('permissions'),
                fn () => $this->resource->getAllPermissions()->pluck('name'),
            ),
            'email_verified_at' => $this->formatDate($this->resource->email_verified_at),
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
            'deleted_at' => $this->formatDate($this->resource->deleted_at),
        ];
    }
}
