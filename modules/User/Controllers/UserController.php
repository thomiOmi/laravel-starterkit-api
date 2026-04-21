<?php

namespace Modules\User\Controllers;

use App\DTOs\DataTableDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\BulkDeleteUserAction;
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
     * @return JsonResponse
     */
    public function index(Request $request)
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
     * @return JsonResponse
     */
    public function store(UserRequest $request, CreateUserAction $action)
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
     * @return JsonResponse
     */
    public function show(string|int $id)
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
     * @return JsonResponse
     */
    public function update(UserRequest $request, string|int $id, UpdateUserAction $action)
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
     * @return JsonResponse
     */
    public function destroy(string|int $id, DeleteUserAction $action)
    {
        $action->execute($id);

        return $this->successResponse(
            null,
            'User deleted successfully'
        );
    }

    /**
     * Remove the specified users from storage in bulk.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  BulkDeleteUserAction  $action  The bulk delete user action.
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request, BulkDeleteUserAction $action)
    {
        $dto = DataTableDTO::fromRequest($request);
        $count = $action->execute($dto->ids);

        return $this->successResponse(
            ['count' => $count],
            'Users deleted successfully'
        );
    }
}
