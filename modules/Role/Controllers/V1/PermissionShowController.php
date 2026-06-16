<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
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
    public function __invoke(Permission $permission): JsonResponse
    {
        $permission = $this->showPermission->handle((string) $permission->id);

        if ($permission === null) {
            return new JsonResponse(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => __('general.not_found', ['resource' => 'Permission']),
                    'data' => null,
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'Permission']),
                'data' => new PermissionResource($permission),
            ],
            Response::HTTP_OK,
        );
    }
}
