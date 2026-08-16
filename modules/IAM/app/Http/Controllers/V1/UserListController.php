<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Http\Requests\V1\UserListRequest;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Models\User;

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
