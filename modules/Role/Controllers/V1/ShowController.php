<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\ShowRoleAction;
use Modules\Role\Models\Role;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class ShowController
{
    public function __construct(
        private ShowRoleAction $showRole
    ) {}

    /**
     * Display the specified role.
     */
    #[Endpoint(operationId: 'showRole', title: 'Show Role')]
    #[ScrambleResponse(status: 200, description: 'Role details retrieved', examples: ['status' => 200, 'message' => 'Role retrieved.', 'data' => ['id' => 1, 'name' => 'admin', 'guard_name' => 'web', 'permissions' => [['id' => 1, 'name' => 'user.list']]]])]
    public function __invoke(Role $role): JsonResponse
    {
        $role = $this->showRole->handle($role->id);

        if ($role === null) {
            return new JsonResponse(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => __('general.not_found', ['resource' => 'Role']),
                    'data' => null,
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'Role']),
                'data' => new RoleResource($role),
            ],
            Response::HTTP_OK,
        );
    }
}
