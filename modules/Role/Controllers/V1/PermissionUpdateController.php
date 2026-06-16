<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Role\Actions\UpdatePermissionAction;
use Modules\Role\Models\Permission;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;

#[Group('Permission Management')]
/**
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
    #[Endpoint(operationId: 'updatePermission', title: 'Update Permission')]
    #[Response(status: 200, description: 'Permission updated successfully', examples: ['status' => 200, 'message' => 'Permission updated.', 'data' => ['id' => 1, 'name' => 'post.create', 'guard_name' => 'web']])]
    public function __invoke(PermissionRequest $request, Permission $permission): JsonDataResponse
    {
        $permission = $this->updatePermission->handle($permission, $request->payload());

        return new JsonDataResponse(
            data: new PermissionResource($permission),
            message: __('messages.updated', ['resource' => 'Permission'])
        );
    }
}
