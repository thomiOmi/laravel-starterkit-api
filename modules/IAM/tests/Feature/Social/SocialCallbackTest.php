<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\SocialCallbackController;
use Modules\IAM\Models\Role;
use Modules\IAM\Support\SocialState;

covers(SocialCallbackController::class);

/**
 * Mock Socialite's driver so the callback resolves the given provider user.
 */
function mockSocialiteUserForCallback(string $provider, string $providerId, ?string $email): void
{
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getId')->andReturn($providerId);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->shouldReceive('getName')->andReturn('Social User');
    $socialUser->shouldReceive('getNickname')->andReturn(null);
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('stateless->user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

beforeEach(function (): void {
    Role::query()->firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
});

describe('GET /api/v1/auth/social/{provider}/callback', function (): void {
    it('returns user and token on valid callback', function (): void {
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
            ->and($response->json('data.user.status'))->toBe(UserStatusEnum::Active->value)
            ->and($response->json('data.access_token'))->not->toBeEmpty();
    });

    it('rejects a blocked user with an existing social account', function (UserStatusEnum $status): void {
        $user = UserFactory::new()->social('google')->createOne([
            'email' => "{$status->value}-social@example.com",
            'password' => 'password123',
            'status' => $status,
        ]);

        mockSocialiteUserForCallback(
            'google',
            $user->socialAccounts()->firstOrFail()->provider_id,
            $user->email,
        );

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        assertProblemResponse($loginResponse, 422, 'validation');

        $oauthState = SocialState::create('login');
        $response = $this->getJson("/api/v1/auth/social/google/callback?state={$oauthState}");

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('detail'))->toBe($loginResponse->json('detail'))
            ->and($response->json('errors.email'))->toBe($loginResponse->json('errors.email'))
            ->and($user->tokens()->count())->toBe(0);
    })->with([
        'banned' => UserStatusEnum::Banned,
        'suspended' => UserStatusEnum::Suspended,
        'inactive' => UserStatusEnum::Inactive,
    ]);

    it('rejects an unverified email match for a banned user without side effects', function (): void {
        $user = UserFactory::new()->banned()->createOne([
            'email' => 'banned-email-match@example.com',
            'email_verified_at' => null,
        ]);

        mockSocialiteUserForCallback('google', 'google-banned-email-match', $user->email);

        $oauthState = SocialState::create('login');
        $response = $this->getJson("/api/v1/auth/social/google/callback?state={$oauthState}");

        assertProblemResponse($response, 422, 'validation');

        $user->refresh();

        expect($response->json('detail'))->toBe(__($user->status->blockedMessageKey()))
            ->and($user->email_verified_at)->toBeNull()
            ->and($user->status)->toBe(UserStatusEnum::Banned)
            ->and($user->socialAccounts()->count())->toBe(0)
            ->and($user->tokens()->count())->toBe(0);
    });

    it('rejects invalid state', function (): void {
        $response = $this->getJson('/api/v1/auth/social/google/callback?state=bad');

        assertProblemResponse($response, 400);
    });
});
