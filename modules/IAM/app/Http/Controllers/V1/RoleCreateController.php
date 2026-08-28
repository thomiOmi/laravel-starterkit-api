<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\CreateRoleAction;
use Modules\IAM\Http\Requests\V1\RoleRequest;
use Modules\IAM\Http\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RoleCreateController extends Controller
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
        $role->load(['permissions:id,name']);

        return new SuccessResponse(
            data: new RoleResource($role),
            title: 'Created',
            detail: __('general.resource_created', ['resource' => 'Role']),
            status: Response::HTTP_CREATED,
        );
    }
}
