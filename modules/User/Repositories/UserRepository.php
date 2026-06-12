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
     * Get paginated users with filters.
     *
     * @return Paginator<int, User>
     */
    public function paginate(UserFilter $filter, int $perPage = 10): Paginator
    {
        return $filter->apply(User::query())
            ->with(['roles.permissions:id,name', 'permissions:id,name'])
            ->simplePaginate($perPage);
    }

    /**
     * Find a user by ID with caching and Laravel 13 Cache::touch().
     */
    public function findById(string $id): ?User
    {
        $cacheKey = "user:profile:{$id}";

        /** @var User|null $user */
        $user = Cache::remember($cacheKey, now()->addHour(), function () use ($id) {
            return User::with(['roles.permissions:id,name', 'permissions:id,name'])->find($id);
        });

        if ($user) {
            Cache::touch($cacheKey, now()->addHour());
        }

        return $user;
    }

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update an existing user and invalidate cache.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        Cache::forget("user:profile:{$user->id}");

        return $user;
    }

    /**
     * Delete a user and invalidate cache.
     */
    public function delete(User $user): bool
    {
        Cache::forget("user:profile:{$user->id}");

        return (bool) $user->delete();
    }

    /**
     * Bulk delete users and invalidate their caches.
     *
     * @param  array<int, string|int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        foreach ($ids as $id) {
            Cache::forget("user:profile:{$id}");
        }

        /** @var int $count */
        $count = User::whereIn('id', $ids)->delete();

        return $count;
    }

    /**
     * Bulk restore users.
     *
     * @param  array<int, string|int>  $ids
     */
    public function bulkRestore(array $ids): int
    {
        /** @var int $count */
        $count = User::onlyTrashed()->whereIn('id', $ids)->restore();

        return $count;
    }
}
