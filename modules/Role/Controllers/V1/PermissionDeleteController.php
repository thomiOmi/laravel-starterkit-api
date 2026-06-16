<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\DeletePermissionAction;
use Modules\Role\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionDeleteController
{
    public function __construct(
        private DeletePermissionAction $deletePermission
    ) {}

    /**
     * Remove the specified permission.
     */
    #[Endpoint(operationId: 'deletePermission', title: 'Delete Permission')]
    #[ScrambleResponse(status: 204, description: 'Permission deleted successfully')]
    public function __invoke(Permission $permission): JsonResponse
    {
        if ($this->deletePermission->handle($permission)) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_FORBIDDEN,
                'message' => __('general.delete_error', ['resource' => 'Permission']),
                'data' => null,
            ],
            Response::HTTP_FORBIDDEN,
        );
    }
}
