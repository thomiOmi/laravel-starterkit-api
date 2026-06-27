<?php

declare(strict_types=1);

namespace Modules\Role\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Role\Database\Factories\PermissionFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

#[Fillable([
    'name',
    'guard_name',
])]
class Permission extends SpatiePermission
{
    /** @use HasFactory<PermissionFactory> */
    use HasDefaultBehavior, HasFactory;
}
