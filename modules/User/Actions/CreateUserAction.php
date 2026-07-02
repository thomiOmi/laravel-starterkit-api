<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\IAM\Models\User;
use Modules\User\Payloads\V1\UserPayload;

final readonly class CreateUserAction
{
    public function handle(UserPayload $payload): User
    {
        $user = User::create($payload->toArray());

        $user->assignRole('user');

        return $user;
    }
}
