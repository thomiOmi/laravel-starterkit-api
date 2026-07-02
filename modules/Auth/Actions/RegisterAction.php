<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Modules\Auth\Payloads\V1\RegisterPayload;
use Modules\IAM\Models\User;

final readonly class RegisterAction
{
    /**
     * @return array{user: User, access_token: string, token_type: string}
     */
    public function handle(RegisterPayload $payload, ?string $ip = null, ?string $userAgent = null): array
    {
        $user = User::create([
            'name' => $payload->name,
            'email' => $payload->email,
            'password' => $payload->password,
        ]);

        $user->assignRole('user');

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
            'user' => $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
