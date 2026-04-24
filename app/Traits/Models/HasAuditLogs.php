<?php

declare(strict_types=1);

namespace App\Traits\Models;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Trait HasAuditLogs
 *
 * Provides automatic activity logging for models.
 */
trait HasAuditLogs
{
    use LogsActivity;

    /**
     * Get the options for logging activity.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
