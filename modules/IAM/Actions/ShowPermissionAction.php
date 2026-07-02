<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Permission;

final readonly class ShowPermissionAction
{
    public function handle(string $id): ?Permission
    {
        return Permission::query()->find($id);
    }
}
