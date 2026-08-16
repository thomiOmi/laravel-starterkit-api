<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\SocialLinkAction;
use Modules\IAM\Models\User;

final readonly class SocialLinkController extends Controller
{
    public function __construct(
        private SocialLinkAction $socialLink
    ) {}

    /**
     * @return SuccessResponse<array{url: string}>
     */
    public function __invoke(string $provider, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $url = $this->socialLink->handle($provider, $currentUser);

        return new SuccessResponse(
            data: ['url' => $url],
            title: 'OK',
            detail: __('auth.social_link_success'),
        );
    }
}
