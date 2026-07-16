<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ResendVerificationAction
{
    public function handle(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            return __('auth.email_verified');
        }

        $user->sendEmailVerificationNotification();

        return __('auth.email_verification_sent');
    }
}
