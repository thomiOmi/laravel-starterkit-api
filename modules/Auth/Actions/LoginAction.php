<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Str;
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
    ) {}

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
        if (! $this->auth->guard()->attempt(['email' => $payload->email, 'password' => $payload->password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
                'password' => [__('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = $this->auth->guard()->user();

        event(new Login('web', $user, false));

        $abilities = $user->hasRole(['admin', 'super-admin'])
            ? ['*']
            : ['users:read', 'users:write', 'auth:manage'];

        $plainTextToken = Str::random(40);

        /** @var PersonalAccessToken $token */
        $token = $user->tokens()->create([
            'name' => $payload->deviceName ?? $userAgent ?? 'auth_token',
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        /** @var string|int $tokenId */
        $tokenId = $token->getKey();

        return [
            'user' => $user,
            'access_token' => (string) $tokenId.'|'.$plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
