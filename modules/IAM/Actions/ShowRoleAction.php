<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Role;

final readonly class ShowRoleAction
{
    public function handle(string $id): ?Role
    {
        return Role::query()->find($id);
    }
}
