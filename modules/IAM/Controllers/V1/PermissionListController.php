<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Models\Permission;
use Modules\IAM\Requests\V1\PermissionListRequest;
use Modules\IAM\Resources\PermissionResource;

final readonly class PermissionListController extends Controller
{
    /**
     * Display a paginated listing of permissions.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(PermissionListRequest $request): SuccessResponse
    {
        $permissions = Permission::query()
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
            data: PermissionResource::collection($permissions),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Permissions']),
        );
    }
}
