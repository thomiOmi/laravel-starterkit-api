<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Permission;
use Modules\Role\Repositories\PermissionRepository;

final readonly class DeletePermissionAction
{
    public function __construct(
        private PermissionRepository $repository
    ) {}

    public function handle(string|Permission $permission): bool
    {
        if (is_string($permission)) {
            $permission = $this->repository->findById($permission);
        }

        if (! $permission) {
            return false;
        }

        Cache::forget("permission_{$permission->id}");

        return (bool) $permission->delete();
    }
}
