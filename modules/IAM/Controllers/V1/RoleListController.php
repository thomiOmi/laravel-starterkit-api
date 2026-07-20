<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListRolesAction;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Requests\V1\RoleListRequest;
use Modules\IAM\Resources\RoleResource;

final readonly class RoleListController
{
    public function __construct(
        private ListRolesAction $listRoles
    ) {}

    /**
     * Display a paginated listing of the roles.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(RoleListRequest $request): SuccessResponse
    {
        $roles = $this->listRoles->handle(
            filter: new RoleFilter($request),
            perPage: $request->integer('page.size', 15),
            page: $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            data: RoleResource::collection($roles),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Roles']),
        );
    }
}
