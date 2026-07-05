<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
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
     *
     * @return SuccessResponse<PermissionResource>
     *
     * @throws AuthenticationException Full authentication is required to access permission management.
     * @throws AuthorizationException You do not have permission to create permissions.
     * @throws ValidationException The submitted data failed validation rules.
     */
    public function __invoke(PermissionRequest $request): SuccessResponse
    {
        $permission = $this->createPermission->handle($request->payload());

        return new SuccessResponse(
            data: new PermissionResource($permission),
            title: 'Created',
            detail: __('general.created', ['resource' => 'Permission']),
            status: Response::HTTP_CREATED,
        );
    }
}
