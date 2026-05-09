<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\DTOs\ResetPasswordDTO;
use Modules\User\Models\User;

/**
 * Action for resetting the user's password.
 */
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
        $statusInput = Password::broker()->reset(
            $dto->toArray(),
            function ($user, $password) {
                /** @var User $user */
                /** @var string $passwordString */
                $passwordString = $password;

                $user->forceFill([
                    'password' => Hash::make($passwordString),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        /** @var string $status */
        $status = $statusInput;

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }
}
