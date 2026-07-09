<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Role;

final readonly class ShowRoleAction
{
    public function handle(string $id): ?Role
    {
        return Role::select([
            'id',
            'name',
            'description',
            'guard_name',
            'created_at',
            'updated_at',
        ])->with(['permissions:id,name'])->find($id);
    }
}
