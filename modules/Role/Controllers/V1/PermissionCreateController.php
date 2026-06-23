<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\Role\Actions\CreatePermissionAction;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;

/**
 * @authenticated
 */
final readonly class PermissionCreateController
{
    public function __construct(
        private CreatePermissionAction $createPermission
    ) {}

    /**
     * Store a newly created permission.
     */
    public function __invoke(PermissionRequest $request): SuccessResponse
    {
        $permission = $this->createPermission->handle($request->payload());

        return new SuccessResponse(
            'Created',
            __('general.created', ['resource' => 'Permission']),
            new PermissionResource($permission),
            201,
        );
    }
}
