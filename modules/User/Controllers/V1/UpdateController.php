<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Models\User;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class UpdateController
{
    public function __construct(
        private UpdateUserAction $updateUser,
    ) {}

    /**
     * Update the specified user in storage.
     *
     * @param  UserRequest  $request  The validated user update request.
     * @param  User  $user  The user model instance.
     * @return JsonResponse The API response containing the updated user.
     */
    #[Endpoint(operationId: 'updateUser', title: 'Update User')]
    #[Response(
        status: 200,
        description: 'User updated successfully. Returns the updated user profile.',
        examples: [[
            'status' => 200,
            'message' => 'User updated.',
            'data' => ['id' => '01abcd', 'name' => 'John Updated', 'email' => 'john@example.com', 'avatar' => null, 'roles' => ['admin'], 'permissions' => ['user.view'], 'email_verified_at' => '2026-04-23 15:19:09', 'created_at' => '2026-04-23 15:19:09', 'updated_at' => '2026-04-23 16:00:00', 'deleted_at' => null],
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
        description: 'Forbidden — the user does not have the required permissions to update users.',
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
        status: 404,
        description: 'User not found with the given ID (handled by route model binding).',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'message' => 'Not Found',
            'detail' => 'The requested resource does not exist.',
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
            'errors' => ['email' => ['The email has already been taken.']],
        ]],
    )]
    public function __invoke(UserRequest $request, User $user): JsonResponse
    {
        $user = $this->updateUser->handle($user, $request->payload());

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.updated', ['resource' => 'User']),
                'data' => new UserResource($user),
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
