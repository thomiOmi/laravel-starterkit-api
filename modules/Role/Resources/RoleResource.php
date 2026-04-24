<?php

declare(strict_types=1);

namespace Modules\Role\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\Role\Models\Role;

/**
 * @mixin Role
 */
class RoleResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * The unique identifier of the role (ULID).
             *
             * @example "01hpv4n8f8xrd2m8q0e4x8j9v1"
             *
             * @format "ULID"
             */
            'id' => $this->id,

            /**
             * The name of the role.
             *
             * @example "admin"
             */
            'name' => $this->name,

            /**
             * A brief description of the role purpose.
             *
             * @example "Full system administrator access"
             *
             * @default null
             */
            'description' => $this->description,

            /**
             * The list of permission names assigned to this role.
             *
             * @example ["user.view", "user.create", "role.view"]
             */
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->permissions->pluck('name');
            }),

            /**
             * The date time when the role was created.
             *
             * @example "2026-04-23 15:19:09"
             *
             * @format "YYYY-MM-DD HH:mm:ss"
             */
            'created_at' => $this->formatDate($this->created_at),

            /**
             * The date time when the role was last updated.
             *
             * @example "2026-04-23 15:19:09"
             *
             * @format "YYYY-MM-DD HH:mm:ss"
             */
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
