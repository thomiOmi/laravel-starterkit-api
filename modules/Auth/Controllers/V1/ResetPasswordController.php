<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;

#[Group('Auth')]
final readonly class ResetPasswordController
{
    #[Endpoint(operationId: 'resetPassword', title: 'Reset Password')]
    #[Response(
        status: 200,
        description: 'Password has been reset successfully. The user can now log in with the new password.',
        examples: [[
            'status' => 200,
            'message' => 'Password has been reset.',
            'data' => null,
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Validation error — invalid or expired token, mismatched email, or weak password. Includes field-level error details.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'message' => 'Validation Error',
            'detail' => 'The given data was invalid.',
            'errors' => [
                'email' => ['The selected email is invalid.'],
                'password' => ['The password must be at least 8 characters.'],
            ],
        ]],
    )]
    #[Response(
        status: 429,
        description: 'Too many password reset attempts. Rate limited to prevent abuse.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Too Many Requests',
            'status' => 429,
            'message' => 'Too Many Requests',
            'detail' => 'You have exceeded the request rate limit. Please try again later.',
        ]],
    )]
    public function __invoke(Request $request): SuccessResponse
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
            return new SuccessResponse(
                'OK',
                __($status),
            );
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
