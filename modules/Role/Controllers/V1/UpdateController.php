<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\UpdateRoleAction;
use Modules\Role\Models\Role;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class UpdateController
{
    public function __construct(
        private UpdateRoleAction $updateRole,
    ) {}

    /**
     * Update the specified role in storage.
     *
     * @param  RoleRequest  $request  The validated role update request.
     * @param  Role  $role  The role model instance.
     * @return JsonResponse The API response containing the updated role.
     */
    #[Endpoint(operationId: 'updateRole', title: 'Update Role')]
    #[Response(status: 200, description: 'Role updated successfully', examples: ['status' => 200, 'message' => 'Role updated.', 'data' => ['id' => 1, 'name' => 'editor', 'guard_name' => 'web', 'permissions' => []]])]
    public function __invoke(RoleRequest $request, Role $role): JsonResponse
    {
        $role = $this->updateRole->handle($role, $request->payload());

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.updated', ['resource' => 'Role']),
                'data' => new RoleResource($role),
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
