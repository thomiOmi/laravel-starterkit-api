<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IAM\Http\Controllers\V1\SocialCallbackController;
use Modules\IAM\Models\Role;
use Modules\IAM\Support\SocialState;

covers(SocialCallbackController::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
});

describe('GET /api/v1/auth/social/{provider}/callback', function () {
    it('returns user and token on valid callback', function () {
        $state = SocialState::create('login');
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('google-123');
        $socialUser->shouldReceive('getEmail')->andReturn('social@example.com');
        $socialUser->shouldReceive('getName')->andReturn('Social User');
        $socialUser->shouldReceive('getNickname')->andReturn(null);
        $socialUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('stateless->user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->getJson("/api/v1/auth/social/google/callback?state={$state}");

        assertSuccessResponse($response, 200);
        expect($response->json('data.user.email'))->toBe('social@example.com')
            ->and($response->json('data.access_token'))->not->toBeEmpty();
    });

    it('rejects invalid state', function () {
        $response = $this->getJson('/api/v1/auth/social/google/callback?state=bad');

        assertProblemResponse($response, 400);
    });
});
