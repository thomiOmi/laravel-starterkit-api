<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Role;

final readonly class DeleteRoleAction
{
    public function handle(Role $role): bool
    {
        if ($role->name === 'super-admin') {
            return false;
        }

        return (bool) $role->delete();
    }
}
