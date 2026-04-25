<?php

declare(strict_types=1);

namespace Modules\AuditLog\Filters;

use App\Filters\BaseFilter;

/**
 * Class AuditLogFilter
 *
 * Filter for AuditLog model.
 */
class AuditLogFilter extends BaseFilter
{
    /**
     * Filter by log name.
     */
    public function logName(string $value): void
    {
        $this->builder->where('log_name', 'like', "%{$value}%");
    }

    /**
     * Filter by description.
     */
    public function description(string $value): void
    {
        $this->builder->where('description', 'like', "%{$value}%");
    }

    /**
     * Filter by subject type.
     */
    public function subjectType(string $value): void
    {
        $this->builder->where('subject_type', 'like', "%{$value}%");
    }

    /**
     * Filter by causer ID.
     */
    public function causerId(string $value): void
    {
        $this->builder->where('causer_id', $value);
    }

    /**
     * Search filter.
     */
    public function search(string $value): void
    {
        $this->builder->where(function ($query) use ($value) {
            $query->where('description', 'like', "%{$value}%")
                ->orWhere('log_name', 'like', "%{$value}%")
                ->orWhere('subject_type', 'like', "%{$value}%");
        });
    }
}
