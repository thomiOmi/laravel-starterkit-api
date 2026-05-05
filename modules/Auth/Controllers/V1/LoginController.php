<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Requests\LoginRequest;

class LoginController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected LoginAction $loginAction
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request);
        $result = $this->loginAction->execute($dto, $request);

        if (isset($result['two_factor']) && $result['two_factor']) {
            return $this->successResponse($result, __('auth.two_factor_required'));
        }

        return $this->successResponse($result, __('auth.login_success'));
    }
}
