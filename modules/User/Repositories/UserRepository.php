<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Modules\User\Filters\UserFilter;
use Modules\User\Models\User;

final readonly class UserRepository
{
    /**
     * @return Paginator<int, User>
     */
    public function paginate(UserFilter $filter, int $perPage = 10): Paginator
    {
        return $filter->apply(User::query())
            ->with(['roles.permissions:id,name', 'permissions:id,name'])
            ->simplePaginate($perPage);
    }

    public function findById(string|int $id): ?User
    {
        return Cache::remember("user_{$id}", 300, function () use ($id): ?User {
            return User::with(['roles.permissions:id,name', 'permissions:id,name'])->find($id);
        });
    }
}
