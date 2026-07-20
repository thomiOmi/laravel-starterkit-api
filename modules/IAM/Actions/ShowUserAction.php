<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ShowUserAction
{
    public function handle(User $user): User
    {
        return $user->loadMissing(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name']);
    }
}
