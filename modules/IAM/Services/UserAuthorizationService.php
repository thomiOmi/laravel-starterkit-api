<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use App\Enums\RoleEnum;
use App\Models\Sanctum\PersonalAccessToken;
use Modules\IAM\Models\User;

final readonly class UserAuthorizationService
{
    /**
     * Determine token abilities based on the user's roles.
     *
     * @return array<int, string>
     */
    public function determineTokenAbilities(User $user): array
    {
        return $user->hasRole([RoleEnum::Admin->value, RoleEnum::SuperAdmin->value])
            ? ['*']
            : ['users:read', 'users:write', 'auth:manage'];
    }

    /**
     * Create an access token for the user with role-appropriate abilities
     * and attach request metadata.
     *
     * @return array{access_token: string, token_type: string, expires_at: ?string, expires_in: ?int}
     */
    public function createAccessToken(User $user, string $deviceName, ?string $ip = null, ?string $userAgent = null): array
    {
        $expirationMinutes = config()->integer('sanctum.expiration');
        $expiresAt = $expirationMinutes >= 1
            ? now()->addMinutes($expirationMinutes)
            : null;

        $abilities = $this->determineTokenAbilities($user);

        $token = $user->createToken($deviceName, $abilities, $expiresAt);

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        $accessToken->forceFill([
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ])->save();

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'expires_in' => $expiresAt !== null ? $expirationMinutes * 60 : null,
        ];
    }
}
