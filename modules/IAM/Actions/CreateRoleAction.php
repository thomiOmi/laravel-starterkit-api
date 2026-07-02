<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Role;
use Modules\IAM\Payloads\V1\RolePayload;

final readonly class CreateRoleAction
{
    public function handle(RolePayload $payload): Role
    {
        /** @var Role $role */
        $role = Role::create($payload->toArray());

        if ($payload->permissions !== []) {
            $role->syncPermissions($payload->permissions);
        }

        return $role;
    }
}
