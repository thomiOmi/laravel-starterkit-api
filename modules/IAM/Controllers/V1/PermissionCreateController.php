<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\CreatePermissionAction;
use Modules\IAM\Requests\V1\PermissionRequest;
use Modules\IAM\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class PermissionCreateController
{
    public function __construct(
        private CreatePermissionAction $createPermission
    ) {}

    /**
     * Store a newly created permission.
     */
    /**
     * @return SuccessResponse<PermissionResource>
     */
    public function __invoke(PermissionRequest $request): SuccessResponse
    {
        $permission = $this->createPermission->handle($request->payload());

        return new SuccessResponse(
            'Created',
            __('general.created', ['resource' => 'Permission']),
            new PermissionResource($permission),
            Response::HTTP_CREATED,
        );
    }
}
