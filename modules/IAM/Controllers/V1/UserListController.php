<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\UserListRequest;
use Modules\IAM\Resources\UserResource;

final readonly class UserListController extends Controller
{
    /**
     * Display a paginated listing of the users.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(UserListRequest $request): SuccessResponse
    {
        $users = User::query()
            ->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name'])
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
            data: UserResource::collection($users),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Users']),
        );
    }
}
