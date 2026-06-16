<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Requests\V1\LoginRequest;
use Modules\User\Resources\UserResource;

#[Group('Auth')]
final readonly class LoginController
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    #[Endpoint(operationId: 'login', title: 'Login')]
    #[Response(status: 200, description: 'Login successful', examples: ['status' => 200, 'message' => 'Login successful.', 'data' => ['user' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com'], 'access_token' => '1|abc123token', 'token_type' => 'Bearer']])]
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
