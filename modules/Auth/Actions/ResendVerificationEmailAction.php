<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;

/**
 * Action for resending the verification email.
 */
class ResendVerificationEmailAction
{
    /**
     * Execute the resend verification email action.
     *
     * @param  User  $user  The user model.
     *
     * @throws ValidationException
     */
    public function execute(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email already verified.'],
            ]);
        }

        $user->sendEmailVerificationNotification();

        return 'Verification link sent';
    }
}
