<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Modules\Auth\Payloads\V1\RegisterPayload;
use Modules\User\Models\User;

final readonly class RegisterAction
{
    /**
     * @return array{user: User, access_token: string, token_type: string}
     */
    public function handle(RegisterPayload $payload): array
    {
        $user = User::create([
            'name' => $payload->name,
            'email' => $payload->email,
            'password' => $payload->password,
        ]);

        $plainTextToken = Str::random(40);

        /** @var PersonalAccessToken $token */
        $token = $user->tokens()->create([
            'name' => 'register_token',
            'token' => hash('sha256', $plainTextToken),
            'abilities' => ['users:read', 'users:write', 'auth:manage'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $tokenId = $token->getKey();

        return [
            'user' => $user,
            'access_token' => (string) $tokenId.'|'.$plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
