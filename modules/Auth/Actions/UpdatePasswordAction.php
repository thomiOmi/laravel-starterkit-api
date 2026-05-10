<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Traits\PasswordValidationRules;
use Modules\User\Models\User;

/**
 * Action for updating the user's password.
 */
class UpdatePasswordAction
{
    use PasswordValidationRules;

    /**
     * Execute the update password action.
     *
     * @param  Authenticatable  $user  The user model instance.
     * @param  array<string, mixed>  $input  The input data containing current and new password.
     *
     * @throws ValidationException
     */
    public function execute(Authenticatable $user, array $input): void
    {
        if (! $user instanceof User) {
            throw new \InvalidArgumentException('User must be an instance of '.User::class);
        }

        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('auth.password_invalid'),
        ])->validate();

        /** @var string $password */
        $password = $input['password'];

        $user->forceFill([
            'password' => Hash::make($password),
            'password_changed_at' => now(),
        ])->save();
    }
}
