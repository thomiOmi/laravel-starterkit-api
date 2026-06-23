<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;

final readonly class AssignRolesToUserAction
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(User $user, array $roles): User
    {
        $user->syncRoles($roles);

        return $user;
    }
}
