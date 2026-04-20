<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;

class LoginAction
{
    /**
     * @throws ValidationException
     */
    public function execute(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user instanceof User || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang Anda berikan salah.'],
            ]);
        }

        // Hapus token lama jika ingin single-device login (opsional)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
