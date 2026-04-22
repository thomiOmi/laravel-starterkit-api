<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Repositories\RoleRepository;
use Spatie\Permission\Models\Role;

class CreateRoleAction
{
    /**
     * Create a new CreateRoleAction instance.
     */
    public function __construct(protected RoleRepository $repository) {}

    /**
     * Execute the create role action.
     *
     * @param  RoleDTO  $dto  The role data transfer object.
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
