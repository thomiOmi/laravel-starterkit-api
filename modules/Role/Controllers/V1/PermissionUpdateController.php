<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\UpdatePermissionAction;
use Modules\Role\Models\Permission;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;

/**
 * @group Permission Management
 *
 * @authenticated
 */
final readonly class PermissionUpdateController
{
    public function __construct(
        private UpdatePermissionAction $updatePermission
    ) {}

    /**
     * Update the specified permission.
     */
    public function __invoke(PermissionRequest $request, Permission $permission): JsonDataResponse
    {
        $permission = $this->updatePermission->handle($permission, $request->payload());

        return new JsonDataResponse(
            data: new PermissionResource($permission),
            message: __('messages.updated', ['resource' => 'Permission'])
        );
    }
}
