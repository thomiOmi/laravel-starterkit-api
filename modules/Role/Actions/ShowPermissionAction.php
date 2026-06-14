<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Permission;
use Modules\Role\Repositories\PermissionRepository;

final readonly class ShowPermissionAction
{
    public function __construct(
        private PermissionRepository $repository
    ) {}

    public function handle(string $id): ?Permission
    {
        return $this->repository->findById($id);
    }
}
