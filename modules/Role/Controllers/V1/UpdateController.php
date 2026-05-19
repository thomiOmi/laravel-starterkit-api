<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\UpdateRoleAction;
use Modules\Role\Models\Role;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;

/**
 * @tags Role
 */
final readonly class UpdateController
{
    public function __construct(
        private UpdateRoleAction $updateRole,
    ) {}

    /**
     * Update the specified role in storage.
     */
    public function __invoke(RoleRequest $request, Role $role): JsonDataResponse
    {
        $updatedRole = $this->updateRole->handle($role, $request->payload());

        return new JsonDataResponse(
            data: new RoleResource($updatedRole),
            message: __('messages.updated', ['resource' => 'Role'])
        );
    }
}
