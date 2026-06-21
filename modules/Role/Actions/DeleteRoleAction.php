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

    public function handle(string $id): bool
    {
        $role = $this->repository->findById($id);

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
