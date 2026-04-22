<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\DTOs\DataTableDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\DTOs\UserDTO;
use Modules\User\Repositories\UserRepository;
use Modules\User\Requests\UserRequest;
use Modules\User\Resources\UserResource;

class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     *
     * @param  UserRepository  $userRepository  The user repository.
     */
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Display a paginated listing of the users for a data table.
     *
     * @param  Request  $request  The incoming HTTP request.
     */
    public function index(Request $request): JsonResponse
    {
        $dto = DataTableDTO::fromRequest($request);
        $users = $this->userRepository->getDataTable($dto, relations: ['roles']);

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
        $action->execute($id, $dto);

        $user = $this->userRepository->findById($id);

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
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:users,id'],
            'action' => ['required', 'string', 'in:delete,update,restore,forceDelete'],
            'data' => ['nullable', 'array'],
        ]);

        $count = $this->userRepository->bulk(
            $request->input('ids'),
            $request->input('action'),
            $request->input('data', [])
        );

        return $this->successResponse(
            ['count' => $count],
            "Users {$request->input('action')} successfully"
        );
    }
}
