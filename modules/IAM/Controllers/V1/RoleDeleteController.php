<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\DeleteRoleAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class RoleDeleteController
{
    public function __construct(
        private DeleteRoleAction $deleteRole,
    ) {}

    /**
     * Remove the specified role from storage.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(#[CurrentUser] User $currentUser, Role $role): SuccessResponse
    {
        if (! $currentUser->can('delete', $role)) {
            throw new AccessDeniedHttpException(
                __('general.action_forbidden')
            );
        }

        if ($this->deleteRole->handle($role)) {
            return new SuccessResponse(
                data: null,
                title: __('general.resource_deleted', ['resource' => 'Role']),
                detail: __('general.resource_deleted', ['resource' => 'Role']),
                status: Response::HTTP_OK,
            );
        }

        throw new AccessDeniedHttpException(
            __('general.resource_delete_error', ['resource' => 'Role'])
        );
    }
}
