<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Permission;

final readonly class DeletePermissionAction
{
    public function handle(Permission $permission): bool
    {
        return (bool) $permission->delete();
    }
}
