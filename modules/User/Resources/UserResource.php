<?php

declare(strict_types=1);

namespace Modules\User\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\User\Models\User;

/**
 * @mixin User
 */
class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->getPermissionNames();
            }),
            'email_verified_at' => $this->formatDate($this->email_verified_at),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
            'deleted_at' => $this->formatDate($this->deleted_at),
        ];
    }
}
