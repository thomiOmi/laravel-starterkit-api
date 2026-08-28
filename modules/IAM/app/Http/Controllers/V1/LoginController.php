<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\LoginAction;
use Modules\IAM\Http\Requests\V1\LoginRequest;
use Modules\IAM\Http\Resources\UserResource;

/**
 * @unauthenticated
 */
final readonly class LoginController extends Controller
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    /**
     * Login
     *
     * Authenticate a user and issue a Sanctum bearer token.
     *
     * @return SuccessResponse<array{user: UserResource, access_token: string, token_type: string, expires_at: ?string, expires_in: ?int}>
     */
    public function __invoke(LoginRequest $request): SuccessResponse
    {
        $result = $this->loginAction->handle(
            payload: $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );
        $result['user']->load(['roles:id,name,guard_name', 'permissions:id,name']);

        return new SuccessResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
                'expires_in' => $result['expires_in'],
            ],
            title: 'OK',
            detail: __('auth.login_success'),
        );
    }
}
