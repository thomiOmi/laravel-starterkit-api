<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\ShowRoleAction;
use Modules\Role\Models\Role;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Role Management
 *
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
    public function __invoke(Role $role): JsonDataResponse
    {
        $role = $this->showRole->handle($role->id);

        if ($role === null) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NOT_FOUND,
                message: __('messages.not_found', ['resource' => 'Role'])
            );
        }

        return new JsonDataResponse(
            data: new RoleResource($role),
            message: __('messages.retrieved', ['resource' => 'Role'])
        );
    }
}
