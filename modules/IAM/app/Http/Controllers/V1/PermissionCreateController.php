<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\CreatePermissionAction;
use Modules\IAM\Http\Requests\V1\PermissionRequest;
use Modules\IAM\Http\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class PermissionCreateController extends Controller
{
    public function __construct(
        private CreatePermissionAction $createPermission
    ) {}

    /**
     * Store a newly created permission.
     *
     * @return SuccessResponse<PermissionResource>
     */
    public function __invoke(PermissionRequest $request): SuccessResponse
    {
        $permission = $this->createPermission->handle($request->payload());

        return new SuccessResponse(
            data: new PermissionResource($permission),
            title: 'Created',
            detail: __('general.resource_created', ['resource' => 'Permission']),
            status: Response::HTTP_CREATED,
        );
    }
}
