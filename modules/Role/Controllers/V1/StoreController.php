<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\StoreRoleAction;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Role Management
 *
 * @authenticated
 */
final readonly class StoreController
{
    public function __construct(
        private StoreRoleAction $storeRole,
    ) {}

    /**
     * Store a newly created role in storage.
     *
     * @param  RoleRequest  $request  The validated role creation request.
     * @return JsonDataResponse The API response containing the new role.
     */
    public function __invoke(RoleRequest $request): JsonDataResponse
    {
        $role = $this->storeRole->handle($request->payload());

        return new JsonDataResponse(
            data: new RoleResource($role),
            status: Response::HTTP_CREATED,
            message: __('messages.created', ['resource' => 'Role'])
        );
    }
}
