<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Role\Models\Role;
use Modules\Role\Payloads\V1\RolePayload;

/**
 * Action for creating a new role.
 */
final readonly class StoreRoleAction
{
    /**
     * Create a new StoreRoleAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the create role action.
     *
     * @param  RolePayload  $payload  The role payload.
     * @return Role The newly created role instance.
     */
    public function handle(RolePayload $payload): Role
    {
        return $this->database->transaction(function () use ($payload) {
            /** @var Role $role */
            $role = Role::create([
                'name' => $payload->name,
                'description' => $payload->description,
            ]);

            if (! empty($payload->permissions)) {
                $role->syncPermissions($payload->permissions);
            }

            return $role;
        });
    }
}
