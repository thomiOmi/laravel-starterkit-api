<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class CreateController
{
    public function __construct(
        private CreateRoleAction $createRole,
    ) {}

    /**
     * Store a newly created role in storage.
     *
     * @param  RoleRequest  $request  The validated role creation request.
     * @return JsonResponse The API response containing the new role.
     */
    #[Endpoint(operationId: 'createRole', title: 'Create Role')]
    #[ScrambleResponse(status: 201, description: 'Role created successfully', examples: ['status' => 201, 'message' => 'Role created.', 'data' => ['id' => 1, 'name' => 'editor', 'guard_name' => 'web', 'permissions' => []]])]
    public function __invoke(RoleRequest $request): JsonResponse
    {
        $role = $this->createRole->handle($request->payload());

        return new JsonResponse(
            [
                'status' => Response::HTTP_CREATED,
                'message' => __('messages.created', ['resource' => 'Role']),
                'data' => new RoleResource($role),
            ],
            Response::HTTP_CREATED,
        );
    }
}
