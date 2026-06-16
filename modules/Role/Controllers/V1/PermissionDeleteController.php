<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
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
    public function __invoke(Permission $permission): JsonDataResponse
    {
        if ($this->deletePermission->handle($permission)) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NO_CONTENT,
                message: __('messages.deleted', ['resource' => 'Permission'])
            );
        }

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_FORBIDDEN,
            message: __('messages.delete_error', ['resource' => 'Permission'])
        );
    }
}
