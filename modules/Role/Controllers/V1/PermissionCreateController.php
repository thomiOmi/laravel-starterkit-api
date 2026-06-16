<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\CreatePermissionAction;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionCreateController
{
    public function __construct(
        private CreatePermissionAction $createPermission
    ) {}

    /**
     * Store a newly created permission.
     */
    #[Endpoint(operationId: 'createPermission', title: 'Create Permission')]
    #[ScrambleResponse(status: 201, description: 'Permission created successfully', examples: ['status' => 201, 'message' => 'Permission created.', 'data' => ['id' => 1, 'name' => 'post.create', 'guard_name' => 'web']])]
    public function __invoke(PermissionRequest $request): JsonResponse
    {
        $permission = $this->createPermission->handle($request->payload());

        return new JsonResponse(
            [
                'status' => Response::HTTP_CREATED,
                'message' => __('general.created', ['resource' => 'Permission']),
                'data' => new PermissionResource($permission),
            ],
            Response::HTTP_CREATED,
        );
    }
}
