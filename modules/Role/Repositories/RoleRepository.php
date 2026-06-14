<?php

declare(strict_types=1);

namespace Modules\Role\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Models\Role;

final readonly class RoleRepository
{
    /**
     * Get paginated roles with filters.
     *
     * @return Paginator<int, Role>
     */
    public function paginate(RoleFilter $filter, int $perPage = 10): Paginator
    {
        return $filter->apply(Role::query())
            ->with(['permissions:id,name'])
            ->simplePaginate($perPage);
    }

    /**
     * Find a role by ID.
     */
    public function findById(string $id): ?Role
    {
        /** @var Role|null $role */
        $role = Role::with(['permissions:id,name'])->find($id);

        return $role;
    }

    /**
     * Create a new role.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $permissions
     */
    public function create(array $data, array $permissions = []): Role
    {
        /** @var Role $role */
        $role = Role::create($data);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role;
    }

    /**
     * Update an existing role.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $permissions
     */
    public function update(Role $role, array $data, array $permissions = []): Role
    {
        $role->update($data);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role;
    }

    /**
     * Delete a role.
     */
    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }

    /**
     * Bulk delete roles.
     *
     * @param  array<int, string|int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        /** @var int $count */
        $count = Role::whereIn('id', $ids)->delete();

        return $count;
    }

    /**
     * Bulk restore roles.
     *
     * @param  array<int, string|int>  $ids
     */
    public function bulkRestore(array $ids): int
    {
        /** @var int $count */
        $count = Role::onlyTrashed()->whereIn('id', $ids)->restore();

        return $count;
    }
}
