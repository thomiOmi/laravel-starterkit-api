<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Models\Role;
use Modules\Role\Resources\RoleResource;

/**
 * @group Role Management
 *
 * @authenticated
 */
final readonly class ShowController
{
    /**
     * Display the specified role.
     */
    public function __invoke(Role $role): JsonDataResponse
    {
        $role->load(['permissions:id,name']);

        return new JsonDataResponse(
            data: new RoleResource($role),
            message: __('messages.retrieved', ['resource' => 'Role'])
        );
    }
}
