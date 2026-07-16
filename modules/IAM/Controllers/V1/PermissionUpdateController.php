<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\UpdatePermissionAction;
use Modules\IAM\Requests\V1\PermissionRequest;
use Modules\IAM\Resources\PermissionResource;

final readonly class PermissionUpdateController
{
    public function __construct(
        private UpdatePermissionAction $updatePermission
    ) {}

    /**
     * Update the specified permission.
     *
     * @param  PermissionRequest  $request  The validated permission update request.
     * @param  string  $permission  The permission ID.
     * @return SuccessResponse<PermissionResource>
     */
    public function __invoke(PermissionRequest $request, string $permission): SuccessResponse
    {
        $permissionModel = $this->updatePermission->handle($permission, $request->payload());

        return new SuccessResponse(
            data: new PermissionResource($permissionModel),
            title: 'OK',
            detail: __('general.resource_updated', ['resource' => 'Permission']),
        );
    }
}
