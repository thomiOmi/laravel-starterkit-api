<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\User\Filters\UserFilter;
use Modules\User\Models\User;

final readonly class UserRepository
{
    /**
     * @return Paginator<int, User>
     */
    public function paginate(UserFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        return $filter->apply(User::query())
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public function findById(string $id): ?User
    {
        return User::with(['roles.permissions:id,name', 'permissions:id,name'])->find($id);
    }
}
