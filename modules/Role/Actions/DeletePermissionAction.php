<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Permission;
use Modules\Role\Repositories\PermissionRepository;

final readonly class DeletePermissionAction
{
    public function __construct(
        private PermissionRepository $repository
    ) {}

    public function handle(Permission $permission): bool
    {
        return $this->repository->delete($permission);
    }
}
