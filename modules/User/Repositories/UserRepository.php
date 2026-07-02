<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\User;
use Modules\User\Filters\UserFilter;

final readonly class UserRepository
{
    /**
     * @return Paginator<int, User>
     */
    public function paginate(UserFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        return $filter->apply(User::query())
            ->with(['roles.permissions:id,name', 'permissions:id,name'])
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public function findById(string $id): ?User
    {
        return Cache::remember("user_{$id}", 60, function () use ($id): ?User {
            return User::with(['roles.permissions:id,name', 'permissions:id,name'])->find($id);
        });
    }
}
