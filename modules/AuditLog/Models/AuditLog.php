<?php

declare(strict_types=1);

namespace Modules\AuditLog\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Class AuditLog
 *
 * Custom AuditLog model extending Spatie's Activity model.
 */
class AuditLog extends SpatieActivity
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log';
}
