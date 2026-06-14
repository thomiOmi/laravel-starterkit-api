<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;

final readonly class UpdateUserAction
{
    public function handle(User $user, UserPayload $payload): User
    {
        $user->update($payload->toArray());

        return $user;
    }
}
