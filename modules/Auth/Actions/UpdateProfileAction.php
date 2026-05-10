<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;

/**
 * Action for updating the user's profile information.
 */
class UpdateProfileAction
{
    /**
     * Execute the update profile action.
     *
     * @param  Authenticatable  $user  The user model instance.
     * @param  array<string, mixed>  $input  The input data containing name and email.
     *
     * @throws ValidationException
     */
    public function execute(Authenticatable $user, array $input): void
    {
        if (! $user instanceof User) {
            throw new \InvalidArgumentException('User must be an instance of '.User::class);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validate();

        if ($input['email'] !== $user->email) {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'email_verified_at' => null,
            ])->save();

            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }
}
