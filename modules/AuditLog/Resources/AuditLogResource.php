<?php

declare(strict_types=1);

namespace Modules\AuditLog\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Class AuditLogResource
 *
 * Resource for AuditLog model.
 *
 * @mixin \Modules\AuditLog\Models\AuditLog
 */
class AuditLogResource extends BaseResource
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
            'log_name' => $this->log_name,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'properties' => $this->properties,
            'event' => $this->event,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
