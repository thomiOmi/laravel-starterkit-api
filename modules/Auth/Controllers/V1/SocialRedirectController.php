<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\Auth\Actions\SocialRedirectAction;

final readonly class SocialRedirectController
{
    public function __construct(
        private SocialRedirectAction $socialRedirect
    ) {}

    public function __invoke(string $provider): SuccessResponse
    {
        $url = $this->socialRedirect->handle($provider);

        return new SuccessResponse(
            'OK',
            __('auth.social_login_success'),
            ['url' => $url],
        );
    }
}
