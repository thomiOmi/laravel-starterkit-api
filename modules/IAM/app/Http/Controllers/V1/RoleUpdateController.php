<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\UpdateRoleAction;
use Modules\IAM\Http\Requests\V1\RoleRequest;
use Modules\IAM\Http\Resources\RoleResource;
use Modules\IAM\Models\Role;

final readonly class RoleUpdateController extends Controller
{
    public function __construct(
        private UpdateRoleAction $updateRole,
    ) {}

    /**
     * Update the specified role in storage.
     *
     * @param  RoleRequest  $request  The validated role update request.
     * @return SuccessResponse<RoleResource>
     */
    public function __invoke(RoleRequest $request, Role $role): SuccessResponse
    {
        $roleModel = $this->updateRole->handle($role, $request->payload());
        $roleModel->load(['permissions:id,name']);

        return new SuccessResponse(
            data: new RoleResource($roleModel),
            title: 'OK',
            detail: __('general.resource_updated', ['resource' => 'Role']),
        );
    }
}
