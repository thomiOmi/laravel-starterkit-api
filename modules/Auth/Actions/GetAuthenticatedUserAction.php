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
     * @param  User  $user  The authenticated user instance.
     * @return User The user instance with loaded relationships.
     */
    public function handle(User $user): User
    {
        return $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']);
    }
}
