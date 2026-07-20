<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\UpdateRoleAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Requests\V1\RoleRequest;
use Modules\IAM\Resources\RoleResource;

final readonly class RoleUpdateController
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

        return new SuccessResponse(
            data: new RoleResource($roleModel),
            title: 'OK',
            detail: __('general.resource_updated', ['resource' => 'Role']),
        );
    }
}
