<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\UpdateRoleAction;
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
     * @param  string  $id  The role ID.
     * @return SuccessResponse<RoleResource>
     */
    public function __invoke(RoleRequest $request, string $id): SuccessResponse
    {
        $role = $this->updateRole->handle($id, $request->payload());

        return new SuccessResponse(
            data: new RoleResource($role),
            title: 'OK',
            detail: __('general.updated', ['resource' => 'Role']),
        );
    }
}
