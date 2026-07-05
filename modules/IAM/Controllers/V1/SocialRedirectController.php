<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\SocialRedirectAction;

final readonly class SocialRedirectController
{
    public function __construct(
        private SocialRedirectAction $socialRedirect
    ) {}

    /**
     * @return SuccessResponse<array{url: string}>
     */
    public function __invoke(string $provider): SuccessResponse
    {
        $url = $this->socialRedirect->handle($provider);

        return new SuccessResponse(
            data: ['url' => $url],
            title: 'OK',
            detail: __('auth.social_login_success'),
        );
    }
}
