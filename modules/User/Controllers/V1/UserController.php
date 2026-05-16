<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\DTOs\UserDTO;
use Modules\User\Filters\UserFilter;
use Modules\User\Repositories\UserRepository;
use Modules\User\Requests\UserRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags User
 */
class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     *
     * @param  UserRepository    The user repository.
     */
    public function __construct(protected UserRepository ) {}

    /**
     * Display a paginated listing of the users.
     *
     * @param  Request    The incoming HTTP request.
     * @param  UserFilter    The user filters.
     */
    #[QueryParameter(name: 'page', description: 'The page number for pagination.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'per_page', description: 'Number of items per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter users by name or email.', type: 'string', required: false, example: 'john')]
    #[QueryParameter(name: 'sort_by', description: 'Column name to sort by.', type: 'string', required: false, default: 'created_at', example: 'name')]
    #[QueryParameter(name: 'sort_direction', description: 'Sort direction.', type: 'string', required: false, default: 'desc', example: 'asc')]
    #[QueryParameter(name: 'role', description: 'Filter by role name.', type: 'string', required: false, example: 'admin')]
    public function index(Request , UserFilter ): JsonResponse
    {
         = ->userRepository
            ->applyFilter()
            ->paginate(
                perPage: ->integer('per_page', 10),
                relations: ['roles', 'permissions']
            );

        return new JsonDataResponse(
            data: UserResource::collection(),
            message: 'Users retrieved successfully'
        );
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  UserRequest    The user request.
     * @param  CreateUserAction    The create user action.
     * @return JsonResponse The JSON response containing the created user.
     */
    public function store(UserRequest , CreateUserAction ): JsonResponse
    {
         = UserDTO::fromRequest();
         = ->execute();

        return new JsonDataResponse(
            data: new UserResource(),
            status: Response::HTTP_CREATED,
            message: 'User created successfully'
        );
    }

    /**
     * Display the specified user.
     *
     * @param  string|int    The user ID.
     * @return JsonResponse The JSON response containing the user.
     */
    public function show(string|int ): JsonResponse
    {
         = ->userRepository->findById(, relations: ['roles', 'permissions']);

        return new JsonDataResponse(
            data: new UserResource(),
            message: 'User retrieved successfully'
        );
    }

    /**
     * Update the specified user in storage.
     *
     * @param  UserRequest    The user request.
     * @param  string|int    The user ID.
     * @param  UpdateUserAction    The update user action.
     * @return JsonResponse The JSON response containing the updated user.
     */
    public function update(UserRequest , string|int , UpdateUserAction ): JsonResponse
    {
         = UserDTO::fromRequest();
         = ->execute(, );

        return new JsonDataResponse(
            data: new UserResource(),
            message: 'User updated successfully'
        );
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  string|int    The user ID.
     * @param  DeleteUserAction    The delete user action.
     * @return JsonResponse The JSON response indicating success.
     */
    public function destroy(string|int , DeleteUserAction ): JsonResponse
    {
        ->execute();

        return new JsonDataResponse(
            data: null,
            message: 'User deleted successfully'
        );
    }

    /**
     * Perform bulk action on users.
     *
     * @param  BulkActionRequest    The validated bulk action request.
     * @return JsonResponse The JSON response containing the result of the bulk action.
     */
    public function bulkAction(BulkActionRequest ): JsonResponse
    {
        /** @var array{ids: array<int, string|int>, action: string}  */
         = ->validated();

         = ->userRepository->bulk(
            ['ids'],
            ['action'],
        );

         = ['action'];

        return new JsonDataResponse(
            data: ['count' => ],
            message: "Users {$action} successfully"
        );
    }
}
