<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

final readonly class UpdateUserAction
{
    public function handle(User $user, UserPayload $payload): User
    {
        $user->fill($payload->toArray());
        $user->save();

        return $user;
    }
}
