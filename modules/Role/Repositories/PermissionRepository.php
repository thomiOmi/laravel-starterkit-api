<?php

declare(strict_types=1);

namespace Modules\Role\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Models\Permission;

final readonly class PermissionRepository
{
    /**
     * @return Paginator<int, Permission>
     */
    public function paginate(PermissionFilter $filter, int $perPage = 20): Paginator
    {
        return $filter->apply(Permission::query())
            ->orderBy('name')
            ->simplePaginate($perPage);
    }

    public function findById(string $id): ?Permission
    {
        /** @var Permission|null */
        return Permission::find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Permission
    {
        /** @var Permission */
        return Permission::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Permission $permission, array $data): Permission
    {
        $permission->update($data);

        return $permission;
    }

    public function delete(Permission $permission): bool
    {
        return (bool) $permission->delete();
    }
}
