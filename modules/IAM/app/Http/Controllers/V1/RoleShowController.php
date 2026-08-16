<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Http\Resources\RoleResource;
use Modules\IAM\Models\Role;

final readonly class RoleShowController extends Controller
{
    /**
     * Display the specified role.
     *
     * @return SuccessResponse<RoleResource>
     */
    public function __invoke(Request $request, Role $role): SuccessResponse
    {
        $role->load('permissions');

        return new SuccessResponse(
            data: new RoleResource($role),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Role']),
        );
    }
}
