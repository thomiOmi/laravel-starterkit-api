<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ResendVerificationAction
{
    #[\NoDiscard]
    public function handle(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            return __('auth.verified');
        }

        $user->sendEmailVerificationNotification();

        return __('auth.verification_link_sent');
    }
}
