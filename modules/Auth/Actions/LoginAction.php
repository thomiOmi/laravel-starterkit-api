<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Auth\DTOs\LoginDTO;

class LoginAction
{
    /**
     * Execute login action.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function execute(LoginDTO $dto, Request $request): array
    {
        if (! Auth::attempt(['email' => $dto->email, 'password' => $dto->password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = Auth::user();

        $token = $user->createToken(
            $dto->device_name ?? $request->userAgent() ?? 'auth_token',
            ['*'],
        );

        $token->accessToken->forceFill([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ])->save();

        return [
            'user' => $user,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
