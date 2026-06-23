<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;

/**
 * @authenticated
 */
final readonly class CreateController
{
    public function __construct(
        private CreateRoleAction $createRole,
    ) {}

    /**
     * Store a newly created role in storage.
     *
     * @param  RoleRequest  $request  The validated role creation request.
     */
    public function __invoke(RoleRequest $request): SuccessResponse
    {
        $role = $this->createRole->handle($request->payload());

        return new SuccessResponse(
            'Created',
            __('general.created', ['resource' => 'Role']),
            new RoleResource($role),
            201,
        );
    }
}
