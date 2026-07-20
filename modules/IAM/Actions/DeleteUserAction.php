<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class DeleteUserAction
{
    public function handle(User $user): bool
    {
        return $user->delete() ?? false;
    }
}
