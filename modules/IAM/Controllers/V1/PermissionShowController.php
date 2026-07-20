<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\ShowPermissionAction;
use Modules\IAM\Models\Permission;
use Modules\IAM\Resources\PermissionResource;

final readonly class PermissionShowController
{
    public function __construct(
        private ShowPermissionAction $showPermission
    ) {}

    /**
     * Display the specified permission.
     *
     * @return SuccessResponse<PermissionResource>
     */
    public function __invoke(Request $request, Permission $permission): SuccessResponse
    {
        $permissionModel = $this->showPermission->handle($permission);

        return new SuccessResponse(
            data: new PermissionResource($permissionModel),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Permission']),
        );
    }
}
