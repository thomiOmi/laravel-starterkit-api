<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Requests\V1\LoginRequest;
use Modules\User\Resources\UserResource;

final readonly class LoginController
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    public function __invoke(LoginRequest $request): SuccessResponse
    {
        $isFrontend = EnsureFrontendRequestsAreStateful::fromFrontend($request);

        $result = $this->loginAction->handle(
            payload: $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            stateful: $isFrontend,
        );

        $data = [
            'user' => new UserResource($result['user']),
        ];

        if ($result['access_token'] !== null) {
            $data['access_token'] = $result['access_token'];
            $data['token_type'] = $result['token_type'];
        }

        return new SuccessResponse(
            'OK',
            __('auth.login_success'),
            $data,
        );
    }
}
