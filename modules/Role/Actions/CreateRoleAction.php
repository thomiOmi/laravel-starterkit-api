<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

/**
 * Action for creating a new role.
 */
class CreateRoleAction
{
    /**
     * Create a new CreateRoleAction instance.
     *
     * @param  RoleRepository  $repository  The role repository instance.
     */
    public function __construct(protected RoleRepository $repository) {}

    /**
     * Execute the create role action.
     *
     * @param  RoleDTO  $dto  The role data transfer object.
     * @return Role The newly created role instance.
     */
    public function execute(RoleDTO $dto): Role
    {
        /** @var Role $role */
        $role = $this->repository->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);

        if (! empty($dto->permissions)) {
            $role->syncPermissions($dto->permissions);
        }

        return $role;
    }
}
