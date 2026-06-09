<?php

declare(strict_types=1);

namespace Modules\User\Resources;

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
     * @param  Request  $request  The incoming HTTP request.
     * @return array<string, mixed> The transformed resource array.
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * The unique identifier of the user (ULID).
             *
             * @example "01hpv4n8f8xrd2m8q0e4x8j9v1"
             *
             * @format "ULID"
             */
            'id' => $this->resource->id,

            /**
             * The full name of the user.
             *
             * @example "User"
             */
            'name' => $this->resource->name,

            /**
             * The email address of the user.
             *
             * @example "example@example.com"
             *
             * @format email
             */
            'email' => $this->resource->email,

            /**
             * The list of roles assigned to the user.
             *
             * @example ["user"]
             */
            'roles' => $this->whenLoaded('roles', fn () => $this->resource->roles->pluck('name')),

            /**
             * The list of all permissions granted to the user (aggregated from roles and direct permissions).
             *
             * @example ["user.view", "user.create", "role.view"]
             */
            'permissions' => $this->when($this->relationLoaded('roles') || $this->relationLoaded('permissions'), fn () => $this->resource->getAllPermissions()->pluck('name')),

            /**
             * The date time when the user's email was verified.
             *
             * @example "2026-04-23 15:19:09"
             *
             * @format "YYYY-MM-DD HH:mm:ss"
             *
             * @default null
             */
            'email_verified_at' => $this->formatDate($this->resource->email_verified_at),

            /**
             * The date time when the user was created.
             *
             * @example "2026-04-23 15:19:09"
             *
             * @format "YYYY-MM-DD HH:mm:ss"
             */
            'created_at' => $this->formatDate($this->resource->created_at),

            /**
             * The date time when the user was updated.
             *
             * @example "2026-04-23 15:19:09"
             *
             * @format "YYYY-MM-DD HH:mm:ss"
             */
            'updated_at' => $this->formatDate($this->resource->updated_at),

            /**
             * The date time when the user was deleted.
             *
             * @example "2026-04-23 15:19:09"
             *
             * @format "YYYY-MM-DD HH:mm:ss"
             *
             * @default null
             */
            'deleted_at' => $this->formatDate($this->resource->deleted_at),
        ];
    }
}
