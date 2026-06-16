<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\DeleteRoleAction;
use Modules\Role\Models\Role;
use Symfony\Component\HttpFoundation\Response;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class DeleteController
{
    public function __construct(
        private DeleteRoleAction $deleteRole,
    ) {}

    /**
     * Remove the specified role from storage.
     *
     * @param  Role  $role  The role model instance.
     */
    #[Endpoint(operationId: 'deleteRole', title: 'Delete Role')]
    #[ScrambleResponse(status: 204, description: 'Role deleted successfully')]
    public function __invoke(Role $role): JsonResponse
    {
        if ($this->deleteRole->handle($role)) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_FORBIDDEN,
                'message' => __('messages.delete_error', ['resource' => 'Role']),
                'data' => null,
            ],
            Response::HTTP_FORBIDDEN,
        );
    }
}
