<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group User Management
 *
 * @authenticated
 */
final readonly class DestroyController
{
    public function __construct(
        private DeleteUserAction $deleteUser,
    ) {}

    /**
     * Remove the specified user from storage.
     *
     * @param  User  $user  The user model instance.
     */
    public function __invoke(User $user): JsonDataResponse|Response
    {
        if ($this->deleteUser->handle($user)) {
            return response()->noContent();
        }

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_FORBIDDEN,
            message: __('messages.delete_error', ['resource' => 'User'])
        );
    }
}
