<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\SocialLinkController;

covers(SocialLinkController::class);

describe('GET /api/v1/auth/social/{provider}/link', function () {
    it('returns a redirect url carrying a link state for the user', function () {
        $user = loginAsUser();
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('stateless->with->redirect->getTargetUrl')
            ->andReturn('https://accounts.google.com/o/oauth2/auth?state=link-state');
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->getJson('/api/v1/auth/social/google/link');

        assertSuccessResponse($response, 200);
        expect($response->json('data.url'))->toContain('https://accounts.google.com');
    });

    it('rejects linking an already linked provider', function () {
        $user = UserFactory::new()->social('google')->createOne();
        loginAsUser($user);

        $response = $this->getJson('/api/v1/auth/social/google/link');

        assertProblemResponse($response, 400);
    });

    it('rejects unknown providers', function () {
        loginAsUser();

        assertProblemResponse($this->getJson('/api/v1/auth/social/unknown/link'), 400);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/auth/social/google/link')->assertUnauthorized();
    });
});
