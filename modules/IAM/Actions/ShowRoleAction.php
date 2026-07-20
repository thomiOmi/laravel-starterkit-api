<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Role;

final readonly class ShowRoleAction
{
    public function handle(Role $role): Role
    {
        return $role->loadMissing(['permissions:id,name']);
    }
}
