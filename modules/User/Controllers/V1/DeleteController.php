<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
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
    public function __invoke(User $user): JsonDataResponse
    {
        if ($this->deleteUser->handle($user)) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NO_CONTENT,
            );
        }

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_FORBIDDEN,
            message: __('messages.delete_error', ['resource' => 'User'])
        );
    }
}
