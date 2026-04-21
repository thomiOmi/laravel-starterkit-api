<?php

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
     * @return Builder
     */
    protected function applySearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
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
}
