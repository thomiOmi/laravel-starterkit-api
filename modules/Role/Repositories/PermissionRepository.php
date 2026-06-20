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
        return $filter->apply(Permission::query())
            ->orderBy('name')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public function findById(string $id): ?Permission
    {
        return Cache::remember("permission_{$id}", 60, function () use ($id): ?Permission {
            return Permission::find($id);
        });
    }
}
