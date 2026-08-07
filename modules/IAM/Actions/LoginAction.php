<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\LoginPayload;
use Modules\IAM\Services\UserAuthorizationService;

final readonly class LoginAction
{
    public function __construct(
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
        $user = User::query()
            ->select([
                'id',
                'name',
                'email',
                'avatar',
                'password',
                'status',
                'email_verified_at',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->where('email', $payload->email)
            ->first();

        if ($user === null || ! is_string($user->password) || ! Hash::check($payload->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
                'password' => [__('auth.failed')],
            ]);
        }

        if (! $user->status->allowsAuthentication()) {
            throw ValidationException::withMessages([
                'email' => [__($user->status->blockedMessageKey())],
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
