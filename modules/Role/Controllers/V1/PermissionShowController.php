<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\ShowPermissionAction;
use Modules\Role\Models\Permission;
use Modules\Role\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Permission Management
 *
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
