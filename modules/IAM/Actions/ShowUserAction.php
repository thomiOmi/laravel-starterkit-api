<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ShowUserAction
{
    public function handle(string $id): ?User
    {
        return User::query()->find($id);
    }
}
