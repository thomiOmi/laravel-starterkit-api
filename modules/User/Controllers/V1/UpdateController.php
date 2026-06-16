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
    #[Response(status: 200, description: 'User updated successfully', examples: ['status' => 200, 'message' => 'User updated.', 'data' => ['id' => '01abcd', 'name' => 'John Updated', 'email' => 'john@example.com']])]
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
