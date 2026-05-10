<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

/**
 * Action for updating an existing role.
 */
class UpdateRoleAction
{
    /**
     * Create a new UpdateRoleAction instance.
     *
     * @param  RoleRepository  $repository  The role repository instance.
     */
    public function __construct(protected RoleRepository $repository) {}

    /**
     * Execute the update role action.
     *
     * @param  string|int  $id  The role ID.
     * @param  RoleDTO  $dto  The role data transfer object.
     * @return Role The updated role instance.
     */
    public function execute(string|int $id, RoleDTO $dto): Role
    {
        /** @var Role $role */
        $role = $this->repository->update($id, [
            'name' => $dto->name,
            'description' => $dto->description,
        ]);

        $role->syncPermissions($dto->permissions);

        return $role->load('permissions');
    }
}
