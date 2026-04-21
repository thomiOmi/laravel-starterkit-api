<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordAction
{
    /**
     * Execute the forgot password action.
     *
     * @param  array  $credentials  The user credentials (email).
     *
     * @throws ValidationException
     */
    public function execute(array $credentials): string
    {
        $status = Password::broker()->sendResetLink($credentials);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }
}
