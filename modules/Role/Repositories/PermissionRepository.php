<?php

declare(strict_types=1);

namespace Modules\Role\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Models\Permission;

final readonly class PermissionRepository
{
    /**
     * @return Paginator<int, Permission>
     */
    public function paginate(PermissionFilter $filter, int $pageSize = 20, ?int $page = null): Paginator
    {
        $query = $filter->apply(Permission::query());

        return $query->orderBy('name')
            ->paginate($pageSize, $query->getQuery()->columns ?? ['*'], 'page', $page);
    }

    public function findById(string $id): ?Permission
    {
        /** @var Permission|null $permission */
        $permission = Cache::remember("permission_{$id}", 60, function () use ($id): ?Permission {
            /** @var Permission|null $permission */
            $permission = Permission::select([
                'id',
                'name',
                'guard_name',
                'created_at',
                'updated_at',
            ])->find($id);

            return $permission;
        });

        return $permission;
    }
}
