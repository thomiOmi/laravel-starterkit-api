<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Modules\User\Actions\RegisterUserAction;
use Modules\User\DTOs\UserDTO;
use Modules\User\Requests\RegisterRequest;
use Modules\User\Resources\UserResource;

class UserController extends Controller
{
    use ApiResponser;

    public function register(RegisterRequest $request, RegisterUserAction $action)
    {
        $dto = UserDTO::fromRequest($request);
        $user = $action->execute($dto);

        return $this->success(
            new UserResource($user),
            'User berhasil didaftarkan',
            201
        );
    }
}
