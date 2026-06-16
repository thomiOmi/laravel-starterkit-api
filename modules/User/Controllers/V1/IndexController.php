<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\User\Actions\ListUsersAction;
use Modules\User\Filters\UserFilter;
use Modules\User\Resources\UserResource;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class IndexController
{
    public function __construct(
        private ListUsersAction $listUsers
    ) {}

    /**
     * Display a paginated listing of the users.
     */
    #[QueryParameter(name: 'page', description: 'The page number for pagination.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'per_page', description: 'Number of items per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter users by name or email.', type: 'string', required: false, example: 'john')]
    #[QueryParameter(name: 'sort', description: 'Sort columns. Prefix with - for descending order. Comma-separated for multi-column sort.', type: 'string', required: false, example: '-created_at,name')]
    #[QueryParameter(name: 'filter[role]', description: 'Filter by role name.', type: 'string', required: false, example: 'admin')]
    #[Endpoint(operationId: 'listUsers', title: 'List Users')]
    #[Response(status: 200, description: 'Paginated list of users', examples: ['status' => 200, 'message' => 'Users retrieved.', 'data' => [['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com', 'roles' => [], 'permissions' => []]]])]
    public function __invoke(Request $request, UserFilter $filter): JsonDataResponse
    {
        $users = $this->listUsers->handle($filter, $request->integer('per_page', 10));

        return new JsonDataResponse(
            data: UserResource::collection($users),
            message: __('messages.retrieved', ['resource' => 'Users'])
        );
    }
}
