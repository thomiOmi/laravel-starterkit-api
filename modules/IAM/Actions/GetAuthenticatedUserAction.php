<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

/**
 * Action for retrieving the currently authenticated user.
 */
final readonly class GetAuthenticatedUserAction
{
    #[\NoDiscard]
    public function handle(User $user): User
    {
        return $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']);
    }
}
