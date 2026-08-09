<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\SocialUnlinkAction;
use Modules\IAM\Models\User;

final readonly class SocialUnlinkController extends Controller
{
    public function __construct(
        private SocialUnlinkAction $socialUnlink
    ) {}

    /**
     * @return SuccessResponse<null>
     */
    public function __invoke(string $provider, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $this->socialUnlink->handle($currentUser, $provider);

        return new SuccessResponse(
            data: null,
            title: 'OK',
            detail: __('auth.social_unlink_success'),
        );
    }
}
