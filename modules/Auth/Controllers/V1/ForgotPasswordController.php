<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

#[Group('Auth')]
final readonly class ForgotPasswordController
{
    #[Endpoint(operationId: 'forgotPassword', title: 'Forgot Password')]
    #[Response(
        status: 200,
        description: 'Password reset link sent successfully. An email with a reset link will be dispatched if the email exists in the system. Always returns success to prevent email enumeration.',
        examples: [[
            'status' => 200,
            'title' => 'OK',
            'detail' => 'Password reset link sent.',
            'data' => null,
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Validation error — the email field is required and must be a valid email address.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'detail' => 'The given data was invalid.',
            'errors' => [
                'email' => ['The email field is required.'],
            ],
        ]],
    )]
    #[Response(
        status: 429,
        description: 'Too many password reset requests. Rate limited to prevent abuse.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Too Many Requests',
            'status' => 429,
            'detail' => 'You have exceeded the request rate limit. Please try again later.',
        ]],
    )]
    public function __invoke(Request $request): SuccessResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $status = Password::sendResetLink(
            $request->only('email'),
        );

        if ($status === Password::RESET_LINK_SENT) {
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
