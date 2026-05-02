<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
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
     * @param  LoginRequest|null  $request  The optional request to clear rate limits and get device info.
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

        $deviceName = $dto->device_name ?? 'auth_token';
        $ipAddress = $request ? $request->ip() : null;
        $userAgent = $request ? $request->userAgent() : null;

        $tokenInstance = $user->createToken($deviceName);

        // Update the token with additional device info
        /** @var PersonalAccessToken $accessToken */
        $accessToken = $tokenInstance->accessToken;
        $accessToken->forceFill([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ])->save();

        event(new Login('sanctum', $user, false));

        return [
            'user' => $user,
            'access_token' => $tokenInstance->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
