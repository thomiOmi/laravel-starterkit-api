<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\DTOs\ResetPasswordDTO;

class ResetPasswordAction
{
    /**
     * Execute the reset password action.
     *
     * @param  ResetPasswordDTO  $dto  The reset password data transfer object.
     *
     * @throws ValidationException
     */
    public function execute(ResetPasswordDTO $dto): string
    {
        $status = Password::broker()->reset(
            $dto->toArray(),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }
}
