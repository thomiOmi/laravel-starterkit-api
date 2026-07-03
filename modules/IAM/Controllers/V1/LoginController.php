<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\LoginAction;
use Modules\IAM\Requests\V1\LoginRequest;
use Modules\IAM\Resources\UserResource;

final readonly class LoginController
{
    public function __construct(
        private LoginAction $loginAction
    ) {}

    /**
     * @return SuccessResponse<array{user: UserResource, access_token: string, token_type: string}>
     */
    public function __invoke(LoginRequest $request): SuccessResponse
    {
        $result = $this->loginAction->handle(
            payload: $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return new SuccessResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            title: 'OK',
            detail: __('auth.login_success'),
        );
    }
}
