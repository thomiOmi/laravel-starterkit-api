<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\Events\Registered;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\User\Resources\UserResource;

#[Group('Auth')]
final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    #[Endpoint(operationId: 'register', title: 'Register')]
    #[Response(status: 201, description: 'Account created successfully. Returns the newly registered user profile.', type: 'SuccessResponse<UserResource>')]
    #[Response(
        status: 422,
        description: 'Validation error — invalid or missing fields (name, email, password). Returns a ProblemResponse with field-level error details.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'detail' => 'The given data was invalid.',
            'errors' => [
                'email' => ['The email has already been taken.'],
                'password' => ['The password field confirmation does not match.'],
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
            'detail' => 'You have exceeded the request rate limit. Please try again later.',
        ]],
    )]
    public function __invoke(RegisterRequest $request): SuccessResponse
    {
        $user = $this->registerAction->handle($request->payload());

        event(new Registered($user));

        return new SuccessResponse(
            'Created',
            __('auth.registered'),
            new UserResource($user),
            201,
        );
    }
}
