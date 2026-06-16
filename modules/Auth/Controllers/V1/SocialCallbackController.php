<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\SocialCallbackAction;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
final readonly class SocialCallbackController
{
    public function __construct(
        private SocialCallbackAction $socialCallback
    ) {}

    #[Endpoint(operationId: 'socialCallback', title: 'Social Login Callback')]
    #[Response(status: 200, description: 'Social login successful', examples: ['status' => 200, 'message' => 'Login successful.', 'data' => ['user' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@gmail.com'], 'access_token' => '1|abc123token', 'token_type' => 'Bearer']])]
    public function __invoke(string $provider, Request $request): JsonResponse
    {
        $result = $this->socialCallback->handle(
            provider: $provider,
            ipAddress: $request->ip() ?? '0.0.0.0',
            userAgent: $request->userAgent(),
        );

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('auth.social_login_success'),
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
