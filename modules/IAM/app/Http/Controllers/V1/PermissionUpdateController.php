<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\UpdatePermissionAction;
use Modules\IAM\Http\Requests\V1\PermissionRequest;
use Modules\IAM\Http\Resources\PermissionResource;
use Modules\IAM\Models\Permission;

final readonly class PermissionUpdateController extends Controller
{
    public function __construct(
        private UpdatePermissionAction $updatePermission
    ) {}

    /**
     * Update the specified permission.
     *
     * @param  PermissionRequest  $request  The validated permission update request.
     * @return SuccessResponse<PermissionResource>
     */
    public function __invoke(PermissionRequest $request, Permission $permission): SuccessResponse
    {
        $permissionModel = $this->updatePermission->handle($permission, $request->payload());

        return new SuccessResponse(
            data: new PermissionResource($permissionModel),
            title: 'OK',
            detail: __('general.resource_updated', ['resource' => 'Permission']),
        );
    }
}
