<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\DeletePermissionAction;
use Modules\IAM\Models\Permission;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class PermissionDeleteController
{
    public function __construct(
        private DeletePermissionAction $deletePermission
    ) {}

    /**
     * Remove the specified permission.
     *
     * @return SuccessResponse<null>|ProblemResponse
     */
    public function __invoke(Request $request, Permission $permission): SuccessResponse|ProblemResponse
    {
        if ($this->deletePermission->handle($permission)) {
            return new SuccessResponse(null, status: SymfonyResponse::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: __('auth.http_forbidden'),
            status: SymfonyResponse::HTTP_FORBIDDEN,
            detail: __('general.resource_delete_error', ['resource' => 'Permission']),
        );
    }
}
