<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Requests\V1\LoginRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
final readonly class LoginController
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    #[Endpoint(operationId: 'login', title: 'Login')]
    #[Response(status: 200, description: 'Login successful', examples: ['status' => 200, 'message' => 'Login successful.', 'data' => ['user' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com'], 'access_token' => '1|abc123token', 'token_type' => 'Bearer']])]
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->handle(
            payload: $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('auth.login_success'),
                'data' => [
                    'user' => new UserResource($result['user']),
                    'access_token' => $result['access_token'],
                    'token_type' => $result['token_type'],
                ],
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
