<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\LoginPayload;
use Modules\IAM\Services\UserAuthorizationService;

final readonly class LoginAction
{
    public function __construct(
        private Hasher $hasher,
        private UserAuthorizationService $authorization,
    ) {}

    /**
     * @return array{user: User, access_token: string, token_type: string, expires_at: ?string, expires_in: ?int}
     *
     * @throws ValidationException
     */
    public function handle(LoginPayload $payload, ?string $ip = null, ?string $userAgent = null): array
    {
        /** @var User|null $user */
        $user = User::with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name'])
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

        $token = $this->authorization->createAccessToken(
            $user,
            $payload->deviceName ?? $userAgent ?? 'auth_token',
            $ip,
            $userAgent,
        );

        return [
            'user' => $user,
            ...$token,
        ];
    }
}
