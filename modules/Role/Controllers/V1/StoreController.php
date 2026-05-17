<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\StoreRoleAction;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Role
 */
final readonly class StoreController
{
    public function __construct(
        private StoreRoleAction $storeRole,
    ) {}

    /**
     * Store a newly created role in storage.
     */
    public function __invoke(RoleRequest $request): JsonDataResponse
    {
        $role = $this->storeRole->handle($request->payload());

        return new JsonDataResponse(
            data: new RoleResource($role),
            status: Response::HTTP_CREATED,
            message: 'Role created successfully'
        );
    }
}
