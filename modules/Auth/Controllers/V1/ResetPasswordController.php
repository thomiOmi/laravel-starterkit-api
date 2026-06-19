<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\Auth\Requests\V1\ResetPasswordRequest;

#[Group('Auth')]
final readonly class ResetPasswordController
{
    public function __construct(
        private ResetPasswordAction $resetPasswordAction
    ) {}

    #[Endpoint(operationId: 'resetPassword', title: 'Reset Password')]
    #[Response(
        status: 200,
        description: 'Password has been reset successfully. The user can now log in with the new password.',
        examples: [[
            'status' => 200,
            'title' => 'OK',
            'detail' => 'Password has been reset.',
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
            'detail' => 'You have exceeded the request rate limit. Please try again later.',
        ]],
    )]
    public function __invoke(ResetPasswordRequest $request): SuccessResponse
    {
        $this->resetPasswordAction->handle([
            'token' => $request->string('token')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'password_confirmation' => $request->string('password_confirmation')->toString(),
        ]);

        return new SuccessResponse(
            'OK',
            __('passwords.reset'),
        );
    }
}
