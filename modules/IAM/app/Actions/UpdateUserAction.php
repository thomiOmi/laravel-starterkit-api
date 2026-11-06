<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

final readonly class UpdateUserAction
{
    public function handle(User $user, UserPayload $payload): User
    {
        $emailChanged = $payload->email !== $user->email;

        $user->fill($payload->toArray());

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }
}
