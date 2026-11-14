<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
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
        $result = DB::transaction(function () use ($payload, $ip, $userAgent): array {
            $user = User::query()->create([
                'name' => $payload->name,
                'email' => $payload->email,
                'password' => $payload->password,
            ]);

            $user->assignRole(RoleEnum::User);

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
        });

        // Fired after the transaction commits so a rolled-back registration
        // never triggers a verification mail.
        event(new Registered($result['user']));

        return $result;
    }
}
