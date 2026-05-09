<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Models\User;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a user by their email address.
     *
     * @param  string  $email  The email address.
     * @return User|null The user instance if found, otherwise null.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Apply a search query to the database query for users.
     *
     * @param  Builder<User>  $query  The query builder instance.
     * @param  string  $search  The search query string.
     * @return Builder<User> The updated query builder instance.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Get the columns that can be filtered for users.
     *
     * @return array<int, string> The list of filterable columns.
     */
    protected function getFilterableColumns(): array
    {
        return ['name', 'email'];
    }

    /**
     * Get the columns that can be sorted for users.
     *
     * @return array<int, string> The list of sortable columns.
     */
    protected function getSortableColumns(): array
    {
        return ['name', 'email', 'created_at'];
    }
}
