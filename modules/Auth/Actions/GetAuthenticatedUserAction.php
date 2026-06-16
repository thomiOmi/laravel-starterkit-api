<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Modules\User\Models\User;

/**
 * Action for retrieving the currently authenticated user with cache orchestration.
 */
final readonly class GetAuthenticatedUserAction
{
    /**
     * Execute the action to get the current user profile.
     *
     * @param  User  $user  The authenticated user.
     * @return User The user instance.
     */
    public function handle(User $user): User
    {
        return $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']);
    }
}
