<?php

declare(strict_types=1);

namespace App\Models\Activity;

use App\Traits\Models\HasTenant;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'event',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'tenant_id',
    ];
}
