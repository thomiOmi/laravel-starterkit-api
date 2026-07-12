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
     * @param  string  $id  The permission ID.
     * @return SuccessResponse<PermissionResource>
     */
    public function __invoke(PermissionRequest $request, string $id): SuccessResponse
    {
        $permission = $this->updatePermission->handle($id, $request->payload());

        return new SuccessResponse(
            data: new PermissionResource($permission),
            title: 'OK',
            detail: __('general.updated', ['resource' => 'Permission']),
        );
    }
}
