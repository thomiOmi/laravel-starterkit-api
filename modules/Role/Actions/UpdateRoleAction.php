<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

class UpdateRoleAction
{
    /**
     * Create a new UpdateRoleAction instance.
     */
    public function __construct(protected RoleRepository $repository) {}

    /**
     * Execute the update role action.
     *
     * @param  string|int  $id  The role ID.
     * @param  RoleDTO  $dto  The role data transfer object.
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
