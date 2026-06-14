<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\DeletePermissionAction;
use Modules\Role\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Permission Management
 *
 * @authenticated
 */
final readonly class PermissionDestroyController
{
    public function __construct(
        private DeletePermissionAction $deletePermission
    ) {}

    /**
     * Remove the specified permission.
     */
    public function __invoke(Permission $permission): JsonDataResponse
    {
        $this->deletePermission->handle($permission);

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_NO_CONTENT,
            message: __('messages.deleted', ['resource' => 'Permission'])
        );
    }
}
