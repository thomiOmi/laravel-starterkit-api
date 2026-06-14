<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Auth\Actions\SocialRedirectAction;

/**
 * @tags Auth
 */
final readonly class SocialRedirectController
{
    public function __construct(
        private SocialRedirectAction $socialRedirect
    ) {}

    public function __invoke(string $provider): JsonDataResponse
    {
        $url = $this->socialRedirect->handle($provider);

        return new JsonDataResponse(
            data: ['url' => $url],
            message: __('auth.social_login_success')
        );
    }
}
