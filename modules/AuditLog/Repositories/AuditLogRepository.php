<?php

declare(strict_types=1);

namespace Modules\AuditLog\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Modules\AuditLog\Models\AuditLog;

/**
 * Class AuditLogRepository
 *
 * Repository for AuditLog model.
 *
 * @extends BaseRepository<AuditLog>
 */
class AuditLogRepository extends BaseRepository
{
    /**
     * AuditLogRepository constructor.
     */
    public function __construct(AuditLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Get filterable columns.
     */
    protected function getFilterableColumns(): array
    {
        return ['log_name', 'description', 'subject_type', 'causer_id', 'event'];
    }

    /**
     * Get sortable columns.
     */
    protected function getSortableColumns(): array
    {
        return ['id', 'log_name', 'description', 'created_at'];
    }

    /**
     * Apply search query.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhere('log_name', 'like', "%{$search}%")
                ->orWhere('subject_type', 'like', "%{$search}%")
                ->orWhere('event', 'like', "%{$search}%");
        });
    }
}
