<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Role;
use Modules\IAM\Payloads\V1\RolePayload;

final readonly class UpdateRoleAction
{
    /**
     * Handle the update of an existing role's details and permissions.
     *
     * Supports both a pre-loaded Spatie Role contract or a string ID for flexibility and performance.
     * Uses fill() and save() pattern for correct model observers and dirty checks.
     *
     * @param  \Spatie\Permission\Contracts\Role|string  $role  The Role model instance or the string ID of the role.
     * @param  RolePayload  $payload  The data payload containing update information.
     * @return Role The updated Role model instance.
     */
    public function handle(\Spatie\Permission\Contracts\Role|string $role, RolePayload $payload): Role
    {
        if (is_string($role)) {
            $role = Role::query()->findOrFail($role);
        }

        /** @var Role $role */
        $role->fill($payload->toArray());
        $role->save();

        if ($payload->permissions !== []) {
            $role->syncPermissions($payload->permissions);
        }

        return $role->loadMissing(['permissions:id,name']);
    }
}
