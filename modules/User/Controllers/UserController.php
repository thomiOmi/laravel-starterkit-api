<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Modules\User\Actions\RegisterUserAction;
use Modules\User\DTOs\UserDTO;
use Modules\User\Repositories\UserRepository;
use Modules\User\Requests\RegisterRequest;
use Modules\User\Resources\UserResource;

class UserController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function index()
    {
        $users = $this->userRepository->paginate(15);

        return $this->paginateResponse($users, UserResource::class, 'Users retrieved successfully');
    }

    public function register(RegisterRequest $request, RegisterUserAction $action)
    {
        $dto = UserDTO::fromRequest($request);
        $user = $action->execute($dto);

        return $this->successResponse(
            new UserResource($user),
            'User berhasil didaftarkan',
            201
        );
    }
}
