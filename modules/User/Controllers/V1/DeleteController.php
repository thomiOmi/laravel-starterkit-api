<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class DeleteController
{
    public function __construct(
        private DeleteUserAction $deleteUser,
    ) {}

    /**
     * Remove the specified user from storage.
     *
     * @param  User  $user  The user model instance.
     */
    #[Endpoint(operationId: 'deleteUser', title: 'Delete User')]
    #[ScrambleResponse(status: 204, description: 'User deleted successfully')]
    public function __invoke(User $user): JsonResponse
    {
        if ($this->deleteUser->handle($user)) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_FORBIDDEN,
                'message' => __('messages.delete_error', ['resource' => 'User']),
                'data' => null,
            ],
            Response::HTTP_FORBIDDEN,
        );
    }
}
