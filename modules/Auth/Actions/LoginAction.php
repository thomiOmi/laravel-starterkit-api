<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Requests\LoginRequest;
use Modules\User\Models\User;

class LoginAction
{
    /**
     * Execute the login action.
     *
     * @param  LoginDTO  $dto  The login data transfer object.
     * @param  LoginRequest|null  $request  The optional request to clear rate limits.
     * @return array{user: User, access_token: string, token_type: string}
     *
     * @throws ValidationException
     */
    public function execute(LoginDTO $dto, ?LoginRequest $request = null): array
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $dto->email)->first();

        if (! $user instanceof User || ! Hash::check($dto->password, $user->password)) {
            if ($request) {
                RateLimiter::hit($request->throttleKey());
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
                'password' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($request) {
            RateLimiter::clear($request->throttleKey());
        }

        // =========================================================================
        // DEVICE MANAGEMENT (Multi-Device vs Single-Device)
        // =========================================================================
        // This starterkit supports MULTI-DEVICE login by default.
        // Each login generates a unique token for the device.
        //
        // If you want to enforce SINGLE-DEVICE login (logging in on a new device
        // automatically logs out all other devices), simply UNCOMMENT the line below:
        // =========================================================================
        // $user->tokens()->delete();

        $token = $user->createToken($dto->device_name ?? 'auth_token')->plainTextToken;

        event(new Login('sanctum', $user, false));

        return [
            'user' => $user,
            /**
             * The access token for authentication.
             *
             * @example "1|8d8t2qmIbLUkwylh5aktEXGXVPMwYucUAOYPtihpf9bd84c8"
             */
            'access_token' => $token,
            /**
             * The type of token.
             *
             * @example "Bearer"
             */
            'token_type' => 'Bearer',
        ];
    }
}
