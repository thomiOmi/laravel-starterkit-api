<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ShowUserAction
{
    #[\NoDiscard]
    public function handle(string $id): ?User
    {
        return User::with(['roles.permissions:id,name', 'permissions:id,name'])->find($id);
    }
}
