<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Requests\V1\LoginRequest;
use Modules\User\Resources\UserResource;

#[Group('Auth')]
final readonly class LoginController
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    /**
     * @return SuccessResponse<array{user: UserResource, access_token: string, token_type: string}>
     */
    #[Endpoint(operationId: 'login', title: 'Login')]
    #[Response(
        status: 200,
        description: 'Authentication successful. Returns the authenticated user profile with a Bearer access token for subsequent API requests.',
        examples: [[
            'status' => 200,
            'title' => 'OK',
            'detail' => 'Login successful.',
            'data' => [
                'user' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com', 'avatar' => null, 'roles' => ['admin'], 'permissions' => ['user.view', 'user.create', 'role.view'], 'email_verified_at' => '2026-04-23 15:19:09', 'created_at' => '2026-04-23 15:19:09', 'updated_at' => '2026-04-23 15:19:09', 'deleted_at' => null],
                'access_token' => '1|abc123token',
                'token_type' => 'Bearer',
            ],
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Validation error — the provided credentials are invalid or missing required fields (email, password). Returns a ProblemResponse with field-level error details.',
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
        description: 'Too many login attempts. Rate limited to prevent brute-force attacks. Wait before retrying.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Too Many Requests',
            'status' => 429,
            'detail' => 'You have exceeded the request rate limit. Please try again later.',
        ]],
    )]
    public function __invoke(LoginRequest $request): SuccessResponse
    {
        $result = $this->loginAction->handle(
            payload: $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return new SuccessResponse(
            'OK',
            __('auth.login_success'),
            [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
        );
    }
}
