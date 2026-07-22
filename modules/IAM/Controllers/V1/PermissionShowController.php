<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Models\Permission;
use Modules\IAM\Resources\PermissionResource;

final readonly class PermissionShowController
{
    /**
     * Display the specified permission.
     *
     * @return SuccessResponse<PermissionResource>
     */
    public function __invoke(Request $request, Permission $permission): SuccessResponse
    {
        return new SuccessResponse(
            data: new PermissionResource($permission),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Permission']),
        );
    }
}
