<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Payloads\V1\LoginPayload;
use Modules\User\Models\User;

/**
 * Action for handling user login.
 */
final readonly class LoginAction
{
    /**
     * Execute login action.
     *
     * @param  LoginPayload  $payload  The login payload.
     * @param  string|null  $ip  The IP address of the client.
     * @param  string|null  $userAgent  The user agent of the client.
     * @return array{user: User, access_token: string, token_type: string}
     *
     * @throws ValidationException
     */
    public function handle(LoginPayload $payload, ?string $ip = null, ?string $userAgent = null): array
    {
        if (! Auth::attempt(['email' => $payload->email, 'password' => $payload->password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        $token = $user->createToken(
            $payload->deviceName ?? $userAgent ?? 'auth_token',
            ['*'],
        );

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        $accessToken->forceFill([
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ])->save();

        return [
            'user' => $user,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
