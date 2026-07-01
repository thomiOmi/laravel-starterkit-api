<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Payloads\V1\LoginPayload;
use Modules\User\Models\User;

final readonly class LoginAction
{
    public function __construct(
        private Hasher $hasher,
    ) {}

    /**
     * @return array{user: User, access_token: string, token_type: string}
     *
     * @throws ValidationException
     */
    public function handle(LoginPayload $payload, ?string $ip = null, ?string $userAgent = null): array
    {
        /** @var User|null $user */
        $user = User::with(['roles.permissions:id,name', 'permissions:id,name'])
            ->select([
                'id',
                'name',
                'email',
                'avatar',
                'password',
                'email_verified_at',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->where('email', $payload->email)
            ->first();

        if (! $user || ! is_string($user->password) || ! $this->hasher->check($payload->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
                'password' => [__('auth.failed')],
            ]);
        }

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
