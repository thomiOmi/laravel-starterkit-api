<?php

declare(strict_types=1);

namespace Modules\Role\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

use App\Traits\Models\HasDefaultBehavior;

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
    use HasDefaultBehavior;

    protected $keyType = 'string';

    public $incrementing = false;
}
