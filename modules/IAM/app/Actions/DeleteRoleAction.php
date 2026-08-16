<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Modules\IAM\Models\Role;

final readonly class DeleteRoleAction
{
    public function handle(Role $role): bool
    {
        if ($role->name === RoleEnum::SuperAdmin->value) {
            return false;
        }

        return $role->delete() ?? false;
    }
}
