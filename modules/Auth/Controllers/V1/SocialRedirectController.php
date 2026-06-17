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
    #[Response(
        status: 200,
        description: 'Returns the OAuth provider redirect URL. The frontend should redirect the user to this URL to begin the social authentication flow.',
        examples: [[
            'status' => 200,
            'message' => 'Redirect URL generated.',
            'data' => ['url' => 'https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=...&response_type=code&scope=...'],
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Unsupported social provider. Only configured providers (e.g. google, github) are accepted.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'message' => 'Validation Error',
            'detail' => 'The given data was invalid.',
            'errors' => [
                'provider' => ['The selected provider is invalid. Supported providers: google, github.'],
            ],
        ]],
    )]
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
