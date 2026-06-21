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
     * @return Paginator<int, Role>
     */
    public function paginate(RoleFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        return $filter->apply(Role::query())
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public function findById(string $id): ?Role
    {
        return Cache::remember("role_{$id}", 60, function () use ($id): ?Role {
            return Role::with(['permissions:id,name'])->find($id);
        });
    }
}
