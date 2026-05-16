<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Requests\LoginRequest;

/**
 * @tags Auth
 */
class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request);
        $result = $action->execute($dto);

        return new JsonDataResponse(data: $result, message: __('auth.login_success'));
    }
}
