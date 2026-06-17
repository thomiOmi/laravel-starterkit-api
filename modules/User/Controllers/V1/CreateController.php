<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class CreateController
{
    public function __construct(
        private CreateUserAction $createUser,
    ) {}

    /**
     * Store a newly created user in storage.
     *
     * @param  UserRequest  $request  The validated user creation request.
     * @return SuccessResponse The API response containing the new user.
     */
    #[Endpoint(operationId: 'createUser', title: 'Create User')]
    #[Response(
        status: 201,
        description: 'User created successfully. Returns the new user profile.',
        examples: [[
            'status' => 201,
            'message' => 'User created.',
            'data' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com', 'avatar' => null, 'roles' => [], 'permissions' => [], 'email_verified_at' => null, 'created_at' => '2026-04-23 15:19:09', 'updated_at' => '2026-04-23 15:19:09', 'deleted_at' => null],
        ]],
    )]
    #[Response(
        status: 401,
        description: 'Authentication required. The request lacks a valid Bearer token.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
            'message' => 'Unauthenticated',
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
    #[Response(
        status: 403,
        description: 'Forbidden — the user does not have the required permissions to create users.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'message' => 'Forbidden',
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Validation error — the provided data failed validation rules.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'message' => 'Validation Error',
            'detail' => 'The given data was invalid.',
            'errors' => ['email' => ['The email has already been taken.'], 'password' => ['The password must be at least 8 characters.']],
        ]],
    )]
    public function __invoke(UserRequest $request): SuccessResponse
    {
        $user = $this->createUser->handle($request->payload());

        return new SuccessResponse(
            'Created',
            __('general.created', ['resource' => 'User']),
            new UserResource($user),
            201,
        );
    }
}
