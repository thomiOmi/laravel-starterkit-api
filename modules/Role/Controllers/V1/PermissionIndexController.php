<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\ListPermissionsAction;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Resources\PermissionResource;

/**
 * @group Permission Management
 *
 * @authenticated
 */
final readonly class PermissionIndexController
{
    public function __construct(
        private ListPermissionsAction $listPermissions
    ) {}

    /**
     * Display a paginated listing of permissions.
     */
    public function __invoke(Request $request, PermissionFilter $filter): JsonDataResponse
    {
        $permissions = $this->listPermissions->handle($filter, $request->integer('per_page', 20));

        return new JsonDataResponse(
            data: PermissionResource::collection($permissions),
            message: __('messages.retrieved', ['resource' => 'Permissions'])
        );
    }
}
