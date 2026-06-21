<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Modules\User\Models\User;

/**
 * Action for retrieving the currently authenticated user.
 */
final readonly class GetAuthenticatedUserAction
{
    public function handle(User $user): User
    {
        return $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']);
    }
}
