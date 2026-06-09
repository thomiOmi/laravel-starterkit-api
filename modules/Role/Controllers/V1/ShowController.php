<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\DataResponse;
use Modules\Role\Models\Role;
use Modules\Role\Resources\RoleResource;

/**
 * @tags Role
 */
final readonly class ShowController
{
    /**
     * Display the specified role.
     */
    public function __invoke(Role $role): DataResponse
    {
        $role->load(['permissions']);

        return new DataResponse(
            data: new RoleResource($role),
            message: __('messages.retrieved', ['resource' => 'Role'])
        );
    }
}
