<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;

class RegisterAction
{
    /**
     * Execute the register action.
     *
     * @param  array  $data  The user registration data (name, email, password).
     * @return array{user: User, access_token: string, token_type: string}
     */
    public function execute(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
