<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
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

/**
 * @tags User
 */
class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     *
     * @param  UserRepository  $userRepository  The user repository.
     */
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Display a paginated listing of the users.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  UserFilter  $filter  The user filters.
     */
    #[QueryParameter(name: 'page', description: 'The page number for pagination.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'per_page', description: 'Number of items per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter users by name or email.', type: 'string', required: false, example: 'john')]
    #[QueryParameter(name: 'sort', description: 'Column name to sort by.', type: 'string', required: false, default: 'created_at', example: 'name')]
    #[QueryParameter(name: 'order', description: 'Sort direction.', type: 'string', required: false, default: 'desc', example: 'asc')]
    public function index(Request $request, UserFilter $filter): JsonResponse
    {
        $users = $this->userRepository
            ->applyFilter($filter)
            ->paginate(
                perPage: (int) $request->get('per_page', 10),
                relations: ['roles', 'permissions']
            );

        return $this->paginateResponse($users, UserResource::class, 'Users retrieved successfully');
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  UserRequest  $request  The user request.
     * @param  CreateUserAction  $action  The create user action.
     */
    public function store(UserRequest $request, CreateUserAction $action): JsonResponse
    {
        $dto = UserDTO::fromRequest($request);
        $user = $action->execute($dto);

        return $this->successResponse(
            new UserResource($user),
            'User created successfully',
            201
        );
    }

    /**
     * Display the specified user.
     *
     * @param  string|int  $id  The user ID.
     */
    public function show(string|int $id): JsonResponse
    {
        $user = $this->userRepository->findById($id, relations: ['roles', 'permissions']);

        return $this->successResponse(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * Update the specified user in storage.
     *
     * @param  UserRequest  $request  The user request.
     * @param  string|int  $id  The user ID.
     * @param  UpdateUserAction  $action  The update user action.
     */
    public function update(UserRequest $request, string|int $id, UpdateUserAction $action): JsonResponse
    {
        $dto = UserDTO::fromRequest($request);
        $user = $action->execute($id, $dto);

        return $this->successResponse(
            new UserResource($user),
            'User updated successfully'
        );
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  string|int  $id  The user ID.
     * @param  DeleteUserAction  $action  The delete user action.
     */
    public function destroy(string|int $id, DeleteUserAction $action): JsonResponse
    {
        $action->execute($id);

        return $this->successResponse(
            null,
            'User deleted successfully'
        );
    }

    /**
     * Perform bulk action on users.
     *
     * @param  BulkActionRequest  $request  The validated bulk action request.
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $count = $this->userRepository->bulk(
            $validated['ids'],
            $validated['action'],
        );

        return $this->successResponse(
            ['count' => $count],
            "Users {$validated['action']} successfully"
        );
    }
}
