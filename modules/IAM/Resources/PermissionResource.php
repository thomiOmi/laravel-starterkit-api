<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDate;
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
    use FormatDate;

    /**
     * @return array{id: string, name: string, created_at: ?string, updated_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'created_at' => array_key_exists('created_at', $attributes)
                ? $this->formatDate($this->resource->created_at)
                : null,
            'updated_at' => array_key_exists('updated_at', $attributes)
                ? $this->formatDate($this->resource->updated_at)
                : null,
        ];
    }
}
