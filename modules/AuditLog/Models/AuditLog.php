<?php

declare(strict_types=1);

namespace Modules\AuditLog\Models;

use App\Traits\Models\HasTenant;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Class AuditLog
 *
 * Custom AuditLog model extending Spatie's Activity model.
 */
class AuditLog extends SpatieActivity
{
    use HasTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log';
}
