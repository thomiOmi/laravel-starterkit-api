<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\SocialRedirectAction;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
final readonly class SocialRedirectController
{
    public function __construct(
        private SocialRedirectAction $socialRedirect
    ) {}

    #[Endpoint(operationId: 'socialRedirect', title: 'Social Login Redirect')]
    #[Response(status: 200, description: 'Social login redirect URL', examples: ['status' => 200, 'message' => 'Redirect URL generated.', 'data' => ['url' => 'https://accounts.google.com/o/oauth2/auth?...']])]
    public function __invoke(string $provider): JsonResponse
    {
        $url = $this->socialRedirect->handle($provider);

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('auth.social_login_success'),
                'data' => ['url' => $url],
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
