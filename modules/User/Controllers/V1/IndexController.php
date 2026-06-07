<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Modules\User\Filters\UserFilter;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

/**
 * @tags User
 */
final readonly class IndexController
{
    /**
     * Display a paginated listing of the users.
     */
    #[QueryParameter(name: 'page', description: 'The page number for pagination.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'per_page', description: 'Number of items per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter users by name or email.', type: 'string', required: false, example: 'john')]
    #[QueryParameter(name: 'sort_by', description: 'Column name to sort by.', type: 'string', required: false, default: 'created_at', example: 'name')]
    #[QueryParameter(name: 'sort_direction', description: 'Sort direction.', type: 'string', required: false, default: 'desc', example: 'asc')]
    #[QueryParameter(name: 'role', description: 'Filter by role name.', type: 'string', required: false, example: 'admin')]
    public function __invoke(Request $request, UserFilter $filter): JsonDataResponse
    {
        $users = $filter->apply(User::query())
            ->with(['roles.permissions', 'permissions'])
            ->simplePaginate($request->integer('per_page', 10));

        return new JsonDataResponse(
            data: UserResource::collection($users),
            message: __('messages.retrieved', ['resource' => 'Users'])
        );
    }
}
