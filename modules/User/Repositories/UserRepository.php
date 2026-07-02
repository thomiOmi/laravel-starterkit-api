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
    public function paginate(UserFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        $query = $filter->apply(User::query());

        return $query->with(['roles.permissions:id,name', 'permissions:id,name'])
            ->paginate($pageSize, $query->getQuery()->columns ?? ['*'], 'page', $page);
    }

    public function findById(string $id): ?User
    {
        /** @var User|null $user */
        $user = Cache::remember("user_{$id}", 60, function () use ($id): ?User {
            /** @var User|null $user */
            $user = User::with(['roles.permissions:id,name', 'permissions:id,name'])
                ->select([
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ])
                ->find($id);

            return $user;
        });

        return $user;
    }
}
