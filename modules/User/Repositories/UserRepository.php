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
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Apply a search query to the database query for users.
     *
     * @param  Builder  $query  The query builder.
     * @param  string  $search  The search query.
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
     */
    protected function getFilterableColumns(): array
    {
        return ['name', 'email'];
    }

    /**
     * Get the columns that can be sorted for users.
     */
    protected function getSortableColumns(): array
    {
        return ['name', 'email', 'created_at'];
    }
}
