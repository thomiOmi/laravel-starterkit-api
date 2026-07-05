<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\UpdatePermissionAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\PermissionRequest;
use Modules\IAM\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class PermissionUpdateController
{
    public function __construct(
        private UpdatePermissionAction $updatePermission
    ) {}

    /**
     * Update the specified permission.
     *
     * @param  PermissionRequest  $request  The validated permission update request.
     * @param  string  $id  The permission ID.
     * @return SuccessResponse<PermissionResource>|ProblemResponse
     *
     * @throws AuthenticationException Full authentication is required to access permission management.
     * @throws AuthorizationException You do not have permission to update permissions.
     * @throws ValidationException The submitted data failed validation rules.
     * @throws ModelNotFoundException The specified permission was not found.
     */
    public function __invoke(PermissionRequest $request, string $id): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $permission = $this->updatePermission->handle($id, $request->payload());

        if (! $permission) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'Permission']),
            );
        }

        return new SuccessResponse(
            data: new PermissionResource($permission),
            title: 'OK',
            detail: __('general.updated', ['resource' => 'Permission']),
        );
    }
}
