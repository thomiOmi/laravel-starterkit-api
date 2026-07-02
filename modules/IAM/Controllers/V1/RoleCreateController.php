<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
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
     */
    public function __invoke(RoleRequest $request): SuccessResponse
    {
        $role = $this->createRole->handle($request->payload());

        return new SuccessResponse(
            'Created',
            __('general.created', ['resource' => 'Role']),
            new RoleResource($role),
            Response::HTTP_CREATED,
        );
    }
}
