<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Http\Requests\V1\RoleListRequest;
use Modules\IAM\Http\Resources\RoleResource;
use Modules\IAM\Models\Role;

final readonly class RoleListController extends Controller
{
    /**
     * Display a paginated listing of the roles.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(RoleListRequest $request): SuccessResponse
    {
        $roles = Role::query()
            ->with(['permissions:id,name'])
            ->allowedSearch()
            ->allowedFilters()
            ->allowedSorts()
            ->allowedFields()
            ->allowedIncludes()
            ->paginate(
                perPage: $request->getPerPage(),
                page: $request->getPage(),
            );

        return new SuccessResponse(
            data: RoleResource::collection($roles),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Roles']),
        );
    }
}
