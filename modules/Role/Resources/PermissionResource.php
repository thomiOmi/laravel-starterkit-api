<?php

declare(strict_types=1);

namespace Modules\Role\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\Role\Models\Permission;

/**
 * @property-read Permission $resource
 *
 * @mixin Permission
 */
class PermissionResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'guard_name' => $this->resource->guard_name,
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
        ];
    }
}
