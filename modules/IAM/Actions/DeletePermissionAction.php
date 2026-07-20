<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Permission;

final readonly class DeletePermissionAction
{
    public function handle(Permission $permission): bool
    {
        return $permission->delete() ?? false;
    }
}
