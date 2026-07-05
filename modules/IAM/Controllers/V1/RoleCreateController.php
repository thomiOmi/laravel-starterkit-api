<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\CreateRoleAction;
use Modules\IAM\Requests\V1\RoleRequest;
use Modules\IAM\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RoleCreateController
{
    public function __construct(
        private CreateRoleAction $createRole,
    ) {}

    /**
     * Store a newly created role in storage.
     *
     * @param  RoleRequest  $request  The validated role creation request.
     * @return SuccessResponse<RoleResource>
     *
     * @throws AuthenticationException Full authentication is required to access role management.
     * @throws AuthorizationException You do not have permission to create roles.
     * @throws ValidationException The submitted data failed validation rules.
     */
    public function __invoke(RoleRequest $request): SuccessResponse
    {
        $role = $this->createRole->handle($request->payload());

        return new SuccessResponse(
            data: new RoleResource($role),
            title: 'Created',
            detail: __('general.created', ['resource' => 'Role']),
            status: Response::HTTP_CREATED,
        );
    }
}
