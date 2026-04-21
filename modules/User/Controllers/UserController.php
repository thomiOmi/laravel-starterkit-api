<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Modules\User\Actions\RegisterUserAction;
use Modules\User\DTOs\UserDTO;
use Modules\User\Repositories\UserRepository;
use Modules\User\Requests\RegisterRequest;
use Modules\User\Resources\UserResource;

class UserController extends Controller
{
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = $this->userRepository->paginate(15);

        return $this->paginateResponse($users, UserResource::class, 'Users retrieved successfully');
    }

    /**
     * Handle a user registration request.
     *
     * @param  \Modules\User\Requests\RegisterRequest  $request
     * @param  \Modules\User\Actions\RegisterUserAction  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request, RegisterUserAction $action)
    {
        $dto = UserDTO::fromRequest($request);
        $user = $action->execute($dto);

        return $this->successResponse(
            new UserResource($user),
            'User registered successfully',
            201
        );
    }
}
