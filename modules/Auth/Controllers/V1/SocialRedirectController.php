<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Auth\Actions\SocialRedirectAction;

#[Group('Auth')]
final readonly class SocialRedirectController
{
    public function __construct(
        private SocialRedirectAction $socialRedirect
    ) {}

    #[Endpoint(operationId: 'socialRedirect', title: 'Social Login Redirect')]
    #[Response(status: 200, description: 'Social login redirect URL generated successfully.', type: 'SuccessResponse<array{url: string}>')]
    #[Response(
        status: 422,
        description: 'Unsupported social provider. Only configured providers (e.g. google, github) are accepted.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'detail' => 'The given data was invalid.',
            'errors' => [
                'provider' => ['The selected provider is invalid. Supported providers: google, github.'],
            ],
        ]],
    )]
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
