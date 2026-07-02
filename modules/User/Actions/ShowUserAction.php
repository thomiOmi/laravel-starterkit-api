<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\IAM\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * Action for retrieving a single user profile.
 */
final readonly class ShowUserAction
{
    /**
     * Create a new ShowUserAction instance.
     */
    public function __construct(
        private UserRepository $repository
    ) {}

    /**
     * Execute the show user action.
     *
     * @param  string  $id  The user ID.
     * @return User|null The user instance or null if not found.
     */
    public function handle(string $id): ?User
    {
        return $this->repository->findById($id);
    }
}
