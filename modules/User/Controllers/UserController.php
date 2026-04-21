<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\RegisterAction;
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
     * @return JsonResponse
     */
    public function index()
    {
        $users = $this->userRepository->paginate(15);

        return $this->paginateResponse($users, UserResource::class, 'Users retrieved successfully');
    }

    /**
     * Handle a user registration request.
     *
     * @return JsonResponse
     */
    public function register(RegisterRequest $request, RegisterAction $action)
    {
        $dto = UserDTO::fromRequest($request);
        $result = $action->execute($dto);

        return $this->successResponse(
            new UserResource($result['user']),
            'User registered successfully',
            201
        );
    }
}
