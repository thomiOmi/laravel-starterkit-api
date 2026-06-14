<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Auth\Actions\SocialCallbackAction;
use Modules\User\Resources\UserResource;

/**
 * @tags Auth
 */
final readonly class SocialCallbackController
{
    public function __construct(
        private SocialCallbackAction $socialCallback
    ) {}

    public function __invoke(string $provider): JsonDataResponse
    {
        $result = $this->socialCallback->handle($provider);

        return new JsonDataResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            message: __('auth.social_login_success')
        );
    }
}
