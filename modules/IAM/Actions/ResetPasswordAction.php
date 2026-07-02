<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Models\User;

final readonly class ResetPasswordAction
{
    /**
     * @param  array{token: string, email: string, password: string, password_confirmation: string}  $data
     */
    public function handle(array $data): void
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if (! is_string($status)) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.token')],
            ]);
        }

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }
}
