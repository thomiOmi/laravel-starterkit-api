<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

final readonly class ShowRoleAction
{
    public function __construct(
        private RoleRepository $repository
    ) {}

    public function handle(string $id): ?Role
    {
        return $this->repository->findById($id);
    }
}
