<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\SocialCallbackAction;
use Modules\IAM\Resources\UserResource;

final readonly class SocialCallbackController
{
    public function __construct(
        private SocialCallbackAction $socialCallback
    ) {}

    /**
     * @return SuccessResponse<array{user: UserResource, access_token: string, token_type: string}>
     *
     * @throws AuthenticationException Unable to authenticate with the social provider.
     * @throws ValidationException The social authentication data is invalid.
     */
    public function __invoke(string $provider, Request $request): SuccessResponse
    {
        $result = $this->socialCallback->handle(
            provider: $provider,
            ipAddress: $request->ip() ?? '0.0.0.0',
            userAgent: $request->userAgent(),
        );

        return new SuccessResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            title: 'OK',
            detail: __('auth.social_login_success'),
        );
    }
}
