<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\IAM\Models\Permission;

/**
 * @property-read Permission $resource
 *
 * @mixin Permission
 */
class PermissionResource extends JsonResource
{
    use FormatDates;

    /**
     * @return array{id: string, name: string, guard_name: string, created_at: string, updated_at: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->id,
            'name' => $this->resource->name,
            'guard_name' => $this->resource->guard_name,
            'created_at' => (string) $this->formatDate($this->resource->created_at),
            'updated_at' => (string) $this->formatDate($this->resource->updated_at),
        ];
    }
}
