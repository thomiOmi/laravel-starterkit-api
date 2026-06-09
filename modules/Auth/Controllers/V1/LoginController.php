<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Requests\V1\LoginRequest;
use Modules\Auth\Resources\UserResource;

/**
 * @tags Auth
 */
final readonly class LoginController
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    public function __invoke(LoginRequest $request): JsonDataResponse
    {
        $result = $this->loginAction->handle(
            payload: $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return new JsonDataResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            message: __('auth.login_success')
        );
    }
}
