<?php

namespace Modules\Role\Actions;

use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Repositories\RoleRepository;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    /**
     * Create a new UpdateRoleAction instance.
     */
    public function __construct(protected RoleRepository $repository) {}

    /**
     * Execute the update role action.
     */
    public function execute(string|int $id, RoleDTO $dto): bool
    {
        /** @var Role $role */
        $role = $this->repository->view($id);

        $updated = $role->update(['name' => $dto->name]);

        if ($updated) {
            $role->syncPermissions($dto->permissions);
        }

        return $updated;
    }
}
