<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\DeletePermissionAction;
use Modules\IAM\Models\Permission;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class PermissionDeleteController
{
    public function __construct(
        private DeletePermissionAction $deletePermission
    ) {}

    /**
     * Remove the specified permission.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(Request $request, Permission $permission): SuccessResponse
    {
        if ($this->deletePermission->handle($permission)) {
            return new SuccessResponse(
                data: null,
                title: __('general.resource_deleted', ['resource' => 'Permission']),
                detail: __('general.resource_deleted', ['resource' => 'Permission']),
                status: Response::HTTP_OK,
            );
        }

        throw new AccessDeniedHttpException(
            __('general.resource_delete_error', ['resource' => 'Permission'])
        );
    }
}
