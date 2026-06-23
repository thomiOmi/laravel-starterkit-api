<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;

final readonly class CreateUserAction
{
    public function handle(UserPayload $payload): User
    {
        return User::create($payload->toArray());
    }
}
