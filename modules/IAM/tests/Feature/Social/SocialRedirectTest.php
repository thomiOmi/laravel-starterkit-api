<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Modules\IAM\Http\Controllers\V1\SocialRedirectController;

covers(SocialRedirectController::class);

describe('GET /api/v1/auth/social/{provider}/redirect', function () {
    it('returns redirect url', function () {
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('stateless->with->redirect->getTargetUrl')->andReturn('https://accounts.google.com/o/oauth2/auth?state=xyz');
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->getJson('/api/v1/auth/social/google/redirect');

        assertSuccessResponse($response, 200);
        expect($response->json('data.url'))->toContain('https://accounts.google.com');
    });

    it('rejects unknown provider', function () {
        $response = $this->getJson('/api/v1/auth/social/unknown/redirect');

        assertProblemResponse($response, 400);
    });
});
