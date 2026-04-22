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
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->permissions->pluck('name');
            }),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
