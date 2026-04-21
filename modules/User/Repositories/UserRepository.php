<?php

namespace Modules\User\Repositories;

use App\Repositories\BaseRepository;
use Modules\User\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a user by their email address.
     *
     * @param  string  $email
     * @return \Modules\User\Models\User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Create a new user record.
     *
     * @param  array  $details
     * @return \Modules\User\Models\User
     */
    public function registerUser(array $details): User
    {
        return $this->model->create($details);
    }
}
