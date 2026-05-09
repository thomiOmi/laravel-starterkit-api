<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Requests\LoginRequest;

/**
 * @tags Authentication
 */
class LoginController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected LoginAction $loginAction
    ) {}

    /**
     * Handle user login.
     *
     * @param  LoginRequest  $request  The login request.
     * @return JsonResponse The JSON response with token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request);
        $result = $this->loginAction->execute($dto, $request);

        return $this->successResponse($result, __('auth.login_success'));
    }
}
