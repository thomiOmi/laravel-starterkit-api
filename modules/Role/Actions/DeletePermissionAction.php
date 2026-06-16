<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Permission;

final readonly class DeletePermissionAction
{
    public function handle(Permission $permission): bool
    {
        Cache::forget("permission_{$permission->id}");

        return (bool) $permission->delete();
    }
}
