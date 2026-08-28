<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\SocialCallbackAction;
use Modules\IAM\Http\Resources\UserResource;

final readonly class SocialCallbackController extends Controller
{
    public function __construct(
        private SocialCallbackAction $socialCallback
    ) {}

    /**
     * @return SuccessResponse<array{user: UserResource, access_token: string, token_type: string}>
     */
    public function __invoke(string $provider, Request $request): SuccessResponse
    {
        $result = $this->socialCallback->handle(
            provider: $provider,
            state: $request->string('state')->toString(),
            ipAddress: $request->ip() ?? '0.0.0.0',
            userAgent: $request->userAgent(),
        );
        $result['user']->load(['roles:id,name,guard_name', 'permissions:id,name']);

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
