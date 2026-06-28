<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Modules\Auth\Payloads\V1\RegisterPayload;
use Modules\User\Models\User;

final readonly class RegisterAction
{
    public function __construct(
        private AuthFactory $auth,
    ) {}

    /**
     * @return array{user: User, access_token: null, token_type: null}|array{user: User, access_token: string, token_type: string}
     */
    public function handle(RegisterPayload $payload, ?string $ip = null, ?string $userAgent = null, bool $stateful = false): array
    {
        $user = User::create([
            'name' => $payload->name,
            'email' => $payload->email,
            'password' => $payload->password,
        ]);

        if ($stateful) {
            $this->auth->guard('web')->login($user);

            return [
                'user' => $user,
                'access_token' => null,
                'token_type' => null,
            ];
        }

        $token = $user->createToken(
            $payload->deviceName ?? $userAgent ?? 'register_token',
            ['users:read', 'users:write', 'auth:manage'],
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
