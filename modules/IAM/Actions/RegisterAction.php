<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Auth\Events\Registered;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\RegisterPayload;
use Modules\IAM\Services\UserAuthorizationService;

final readonly class RegisterAction
{
    public function __construct(
        private UserAuthorizationService $authorization,
    ) {}

    /**
     * @return array{user: User, access_token: string, token_type: string, expires_at: ?string, expires_in: ?int}
     */
    public function handle(RegisterPayload $payload, ?string $ip = null, ?string $userAgent = null): array
    {
        $user = User::create([
            'name' => $payload->name,
            'email' => $payload->email,
            'password' => $payload->password,
        ]);

        $user->assignRole('user');

        event(new Registered($user));

        $token = $this->authorization->createAccessToken(
            $user,
            $payload->deviceName ?? $userAgent ?? 'register_token',
            $ip,
            $userAgent,
        );

        return [
            'user' => $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']),
            ...$token,
        ];
    }
}
