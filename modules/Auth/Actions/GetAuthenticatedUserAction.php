<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * Action for retrieving the currently authenticated user.
 */
final readonly class GetAuthenticatedUserAction
{
    /**
     * Create a new GetAuthenticatedUserAction instance.
     */
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Execute the action to get the current user profile.
     *
     * @param  string  $userId  The authenticated user ID.
     * @return User|null The user instance or null.
     */
    public function handle(string $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }
}
