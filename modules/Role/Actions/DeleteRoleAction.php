<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

final readonly class DeleteRoleAction
{
    public function __construct(
        private RoleRepository $repository
    ) {}

    public function handle(string|Role $role): bool
    {
        if (is_string($role)) {
            $role = $this->repository->findById($role);
        }

        if (! $role) {
            return false;
        }

        if ($role->name === Role::SUPER_ADMIN) {
            return false;
        }

        Cache::forget("role_{$role->id}");

        return (bool) $role->delete();
    }
}
