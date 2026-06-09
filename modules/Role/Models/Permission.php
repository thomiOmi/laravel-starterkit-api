<?php

declare(strict_types=1);

namespace Modules\Role\Models;

use App\Traits\Models\HasDefaultBehavior;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasDefaultBehavior;
}
