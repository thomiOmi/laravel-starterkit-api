<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;

class LoginAction
{
    /**
     * Execute the login action.
     *
     * @param  array  $credentials  The user credentials (email, password).
     * @return array{user: User, access_token: string, token_type: string}
     *
     * @throws ValidationException
     */
    public function execute(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user instanceof User || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete old tokens if you want single-device login (optional)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
