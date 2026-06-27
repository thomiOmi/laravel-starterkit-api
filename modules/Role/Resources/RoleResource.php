<?php

declare(strict_types=1);

namespace Modules\Role\Resources;

use App\Http\Resources\Concerns\FormatDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Role\Models\Role;

/**
 * @property-read Role $resource
 *
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    use FormatDates;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The incoming request.
     * @return array<string, mixed> The transformed resource array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->resource->permissions->pluck('name');
            }),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
