<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Payloads\V1\LoginPayload;
use Modules\User\Models\User;

/**
 * Action for handling user login.
 */
final readonly class LoginAction
{
    public function __construct(
        private AuthFactory $auth,
        private Hasher $hasher,
    ) {}

    /**
     * Execute login action.
     *
     * @param  LoginPayload  $payload  The login payload.
     * @param  string|null  $ip  The IP address of the client.
     * @param  string|null  $userAgent  The user agent of the client.
     * @param  bool  $stateful  Whether to use session-based auth.
     * @return array{user: User, access_token: null, token_type: null}|array{user: User, access_token: string, token_type: string}
     *
     * @throws ValidationException
     */
    public function handle(LoginPayload $payload, ?string $ip = null, ?string $userAgent = null, bool $stateful = false): array
    {
        if ($stateful) {
            if (! $this->auth->guard('web')->attempt(['email' => $payload->email, 'password' => $payload->password])) {
                throw ValidationException::withMessages([
                    'email' => [__('auth.failed')],
                    'password' => [__('auth.failed')],
                ]);
            }

            /** @var User $user */
            $user = $this->auth->guard('web')->user();

            event(new Login('web', $user, false));

            return [
                'user' => $user,
                'access_token' => null,
                'token_type' => null,
            ];
        }

        /** @var User|null $user */
        $user = User::where('email', $payload->email)->first();

        if (! $user || ! is_string($user->password) || ! $this->hasher->check($payload->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
                'password' => [__('auth.failed')],
            ]);
        }

        event(new Login('web', $user, false));

        $abilities = $user->hasRole(['admin', 'super-admin'])
            ? ['*']
            : ['users:read', 'users:write', 'auth:manage'];

        $token = $user->createToken(
            $payload->deviceName ?? $userAgent ?? 'auth_token',
            $abilities,
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
