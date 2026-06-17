<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    #[Endpoint(operationId: 'register', title: 'Register')]
    #[Response(
        status: 201,
        description: 'Account created successfully. Returns the new user profile. Roles and permissions may be omitted if not loaded.',
        examples: [[
            'status' => 201,
            'message' => 'Registration successful.',
            'data' => [
                'id' => '01abcd',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'avatar' => null,
                'roles' => ['user'],
                'permissions' => [],
                'email_verified_at' => null,
                'created_at' => '2026-06-17 10:00:00',
                'updated_at' => '2026-06-17 10:00:00',
                'deleted_at' => null,
            ],
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Validation error — name, email, password fields failed validation. Returns a ProblemResponse with field-level error details.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'message' => 'Validation Error',
            'detail' => 'The given data was invalid.',
            'errors' => [
                'email' => ['The email has already been taken.'],
                'password' => ['The password must be at least 8 characters.'],
            ],
        ]],
    )]
    #[Response(
        status: 429,
        description: 'Too many registration attempts. Rate limited to prevent abuse. Wait before retrying.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Too Many Requests',
            'status' => 429,
            'message' => 'Too Many Requests',
            'detail' => 'You have exceeded the request rate limit. Please try again later.',
        ]],
    )]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->registerAction->handle($request->payload());

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_CREATED,
                'message' => __('auth.registered'),
                'data' => new UserResource($user),
            ],
            SymfonyResponse::HTTP_CREATED,
        );
    }
}
