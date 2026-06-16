<?php

declare(strict_types=1);

namespace Modules\Role\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Modules\Role\Database\Factories\RoleFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Permission> $permissions
 */
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasDefaultBehavior, HasFactory;
}
