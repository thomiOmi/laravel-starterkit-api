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
        $query = $filter->apply(Role::query());

        return $query->with(['permissions:id,name'])
            ->paginate($pageSize, $query->getQuery()->columns ?? ['*'], 'page', $page);
    }

    public function findById(string $id): ?Role
    {
        /** @var Role|null $role */
        $role = Cache::remember("role_{$id}", 60, function () use ($id): ?Role {
            /** @var Role|null $role */
            $role = Role::with(['permissions:id,name'])
                ->select([
                    'id',
                    'name',
                    'description',
                    'guard_name',
                    'created_at',
                    'updated_at',
                ])
                ->find($id);

            return $role;
        });

        return $role;
    }
}
