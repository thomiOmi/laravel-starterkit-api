<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Modules\Role\Actions\ShowPermissionAction;
use Modules\Role\Models\Permission;
use Modules\Role\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionShowController
{
    public function __construct(
        private ShowPermissionAction $showPermission
    ) {}

    /**
     * Display the specified permission.
     */
    #[Endpoint(operationId: 'showPermission', title: 'Show Permission')]
    #[ScrambleResponse(status: 200, description: 'Permission details retrieved', examples: ['status' => 200, 'message' => 'Permission retrieved.', 'data' => ['id' => 1, 'name' => 'user.list', 'guard_name' => 'web']])]
    public function __invoke(Permission $permission): JsonDataResponse
    {
        $permission = $this->showPermission->handle((string) $permission->id);

        if ($permission === null) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NOT_FOUND,
                message: __('messages.not_found', ['resource' => 'Permission'])
            );
        }

        return new JsonDataResponse(
            data: new PermissionResource($permission),
            message: __('messages.retrieved', ['resource' => 'Permission'])
        );
    }
}
