<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\ShowRoleAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Resources\RoleResource;

final readonly class RoleShowController
{
    public function __construct(
        private ShowRoleAction $showRole
    ) {}

    /**
     * Display the specified role.
     *
     * @return SuccessResponse<RoleResource>
     */
    public function __invoke(Request $request, Role $role): SuccessResponse
    {
        $roleModel = $this->showRole->handle($role);

        return new SuccessResponse(
            data: new RoleResource($roleModel),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Role']),
        );
    }
}
