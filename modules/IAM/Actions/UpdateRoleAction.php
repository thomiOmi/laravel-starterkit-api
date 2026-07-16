<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Role;
use Modules\IAM\Payloads\V1\RolePayload;

final readonly class UpdateRoleAction
{
    public function handle(string $id, RolePayload $payload): Role
    {
        $role = Role::query()->findOrFail($id);

        $role->update($payload->toArray());

        if ($payload->permissions !== []) {
            $role->syncPermissions($payload->permissions);
        }

        return $role->loadMissing(['permissions:id,name']);
    }
}
