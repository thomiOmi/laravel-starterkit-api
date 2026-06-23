<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Modules\Role\Actions\UpdatePermissionAction;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;

/**
 * @authenticated
 */
final readonly class PermissionUpdateController
{
    public function __construct(
        private UpdatePermissionAction $updatePermission
    ) {}

    /**
     * Update the specified permission.
     *
     * @param  PermissionRequest  $request  The validated permission update request.
     * @param  string  $permission  The permission ID.
     */
    public function __invoke(PermissionRequest $request, string $permission): SuccessResponse|ProblemResponse
    {
        $permission = $this->updatePermission->handle($permission, $request->payload());

        if (! $permission) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'Permission']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.updated', ['resource' => 'Permission']),
            new PermissionResource($permission),
        );
    }
}
