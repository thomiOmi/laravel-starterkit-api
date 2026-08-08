<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\DeleteUserAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class UserDeleteController extends Controller
{
    public function __construct(
        private DeleteUserAction $deleteUser,
    ) {}

    /**
     * Remove the specified user from storage.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(#[CurrentUser] User $currentUser, User $user): SuccessResponse
    {
        if ($currentUser->is($user)) {
            throw new AccessDeniedHttpException(
                __('general.self_delete_forbidden')
            );
        }

        if (! $currentUser->can('delete', $user)) {
            throw new AccessDeniedHttpException(
                __('general.action_forbidden')
            );
        }

        if ($this->deleteUser->handle($user)) {
            return new SuccessResponse(
                data: null,
                title: __('general.resource_deleted', ['resource' => 'User']),
                detail: __('general.resource_deleted', ['resource' => 'User']),
                status: Response::HTTP_OK,
            );
        }

        throw new AccessDeniedHttpException(
            __('general.resource_delete_error', ['resource' => 'User'])
        );
    }
}
