<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\DataResponse;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags User
 */
final readonly class DestroyController
{
    public function __construct(
        private DeleteUserAction $deleteUser,
    ) {}

    /**
     * Remove the specified user from storage.
     */
    public function __invoke(User $user): DataResponse
    {
        $this->deleteUser->handle($user);

        return new DataResponse(
            data: null,
            status: Response::HTTP_OK,
            message: __('messages.deleted', ['resource' => 'User'])
        );
    }
}
