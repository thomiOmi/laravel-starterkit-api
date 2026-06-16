<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Role;

final readonly class DeleteRoleAction
{
    public function handle(Role $role): bool
    {
        if ($role->name === 'super-admin') {
            return false;
        }

        Cache::forget("role_{$role->id}");

        return (bool) $role->delete();
    }
}
