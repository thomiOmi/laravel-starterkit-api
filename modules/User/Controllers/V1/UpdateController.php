<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Repositories\UserRepository;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class UpdateController
{
    public function __construct(
        private UpdateUserAction $updateUser,
        private UserRepository $repository,
    ) {}

    /**
     * Update the specified user in storage.
     *
     * @param  UserRequest  $request  The validated user update request.
     * @param  string  $user  The user ID.
     * @return SuccessResponse|ProblemResponse The API response containing the updated user.
     */
    #[Endpoint(operationId: 'updateUser', title: 'Update User')]
    #[Response(status: 200, description: 'User updated successfully. Returns the updated user profile.', type: 'SuccessResponse<UserResource>')]
    #[Response(
        status: 401,
        description: 'Authentication required. The request lacks a valid Bearer token.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
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
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    #[Response(
        status: 404,
        description: 'User not found with the given ID.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
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
            'detail' => 'The given data was invalid.',
            'errors' => ['email' => ['The email has already been taken.']],
        ]],
    )]
    public function __invoke(UserRequest $request, string $user): SuccessResponse|ProblemResponse
    {
        $model = $this->repository->findById($user);

        if (! $model) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'User']),
            );
        }

        $model = $this->updateUser->handle($model, $request->payload());

        return new SuccessResponse(
            'OK',
            __('general.updated', ['resource' => 'User']),
            new UserResource($model),
        );
    }
}
