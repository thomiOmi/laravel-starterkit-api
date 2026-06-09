<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Role\Models\Role;
use Modules\Role\Payloads\V1\RolePayload;

/**
 * Action for updating an existing role.
 */
final readonly class UpdateRoleAction
{
    /**
     * Create a new UpdateRoleAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the update role action.
     *
     * @param  Role  $role  The role model instance.
     * @param  RolePayload  $payload  The role payload.
     * @return Role The updated role instance.
     */
    public function handle(Role $role, RolePayload $payload): Role
    {
        return $this->database->transaction(function () use ($role, $payload) {
            $role->update($payload->toArray());

            $role->syncPermissions($payload->permissions);

            return $role->load('permissions');
        });
    }
}
