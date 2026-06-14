<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\StorePermissionAction;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Permission Management
 *
 * @authenticated
 */
final readonly class PermissionStoreController
{
    public function __construct(
        private StorePermissionAction $storePermission
    ) {}

    /**
     * Store a newly created permission.
     */
    public function __invoke(PermissionRequest $request): JsonDataResponse
    {
        $permission = $this->storePermission->handle($request->payload());

        return new JsonDataResponse(
            data: new PermissionResource($permission),
            status: Response::HTTP_CREATED,
            message: __('messages.created', ['resource' => 'Permission'])
        );
    }
}
