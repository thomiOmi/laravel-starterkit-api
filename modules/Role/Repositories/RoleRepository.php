<?php

declare(strict_types=1);

namespace Modules\Role\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
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
     * Find a role by ID with caching and Laravel 13 Cache::touch().
     */
    public function findById(string $id): ?Role
    {
        $cacheKey = "role:profile:{$id}";

        /** @var Role|null $role */
        $role = Cache::remember($cacheKey, now()->addHour(), function () use ($id) {
            return Role::with(['permissions:id,name'])->find($id);
        });

        if ($role) {
            Cache::touch($cacheKey, now()->addHour());
        }

        return $role;
    }

    /**
     * Create a new role.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        /** @var Role $role */
        $role = Role::create($data);

        return $role;
    }

    /**
     * Update an existing role and invalidate cache.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        Cache::forget("role:profile:{$role->id}");

        return $role;
    }

    /**
     * Delete a role and invalidate cache.
     */
    public function delete(Role $role): bool
    {
        Cache::forget("role:profile:{$role->id}");

        return (bool) $role->delete();
    }

    /**
     * Bulk delete roles and invalidate their caches.
     *
     * @param  array<int, string|int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        foreach ($ids as $id) {
            Cache::forget("role:profile:{$id}");
        }

        /** @var int $count */
        $count = Role::whereIn('id', $ids)->delete();

        return $count;
    }
}
