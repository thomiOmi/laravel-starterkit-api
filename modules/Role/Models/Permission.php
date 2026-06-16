<?php

declare(strict_types=1);

namespace Modules\Role\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Role\Database\Factories\PermissionFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /** @use HasFactory<PermissionFactory> */
    use HasDefaultBehavior, HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;
}
