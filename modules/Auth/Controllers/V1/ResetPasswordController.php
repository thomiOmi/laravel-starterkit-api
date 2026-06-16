<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
final readonly class ResetPasswordController
{
    #[Endpoint(operationId: 'resetPassword', title: 'Reset Password')]
    #[Response(status: 200, description: 'Password reset successfully', examples: ['status' => 200, 'message' => 'Password has been reset.', 'data' => null])]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', PasswordRule::defaults(), 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if (! is_string($status)) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.reset')],
            ]);
        }

        if ($status === Password::PASSWORD_RESET) {
            return new JsonResponse(
                [
                    'status' => SymfonyResponse::HTTP_OK,
                    'message' => __($status),
                    'data' => null,
                ],
                SymfonyResponse::HTTP_OK,
            );
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
