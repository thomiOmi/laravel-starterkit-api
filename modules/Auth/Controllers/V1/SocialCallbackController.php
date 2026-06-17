<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\SocialCallbackAction;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
final readonly class SocialCallbackController
{
    public function __construct(
        private SocialCallbackAction $socialCallback
    ) {}

    #[Endpoint(operationId: 'socialCallback', title: 'Social Login Callback')]
    #[Response(
        status: 200,
        description: 'Social authentication successful. Returns the authenticated user profile with a Bearer access token.',
        examples: [[
            'status' => 200,
            'message' => 'Login successful.',
            'data' => [
                'user' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@gmail.com', 'avatar' => null, 'roles' => ['user'], 'permissions' => [], 'email_verified_at' => '2026-04-23 15:19:09', 'created_at' => '2026-04-23 15:19:09', 'updated_at' => '2026-04-23 15:19:09', 'deleted_at' => null],
                'access_token' => '1|abc123token',
                'token_type' => 'Bearer',
            ],
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Invalid provider callback data — missing or malformed OAuth code or provider mismatch.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'message' => 'Validation Error',
            'detail' => 'The given data was invalid.',
            'errors' => [
                'provider' => ['The selected provider is invalid.'],
            ],
        ]],
    )]
    #[Response(
        status: 401,
        description: 'Social authentication failed — the provider did not return a valid user or the email is already associated with a different login method.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
            'message' => 'Unauthenticated',
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
    public function __invoke(string $provider, Request $request): JsonResponse
    {
        $result = $this->socialCallback->handle(
            provider: $provider,
            ipAddress: $request->ip() ?? '0.0.0.0',
            userAgent: $request->userAgent(),
        );

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('auth.social_login_success'),
                'data' => [
                    'user' => new UserResource($result['user']),
                    'access_token' => $result['access_token'],
                    'token_type' => $result['token_type'],
                ],
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
