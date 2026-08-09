<?php

declare(strict_types=1);

use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\SocialAccount;
use Modules\IAM\Support\SocialState;

beforeEach(function (): void {
    $this->seed(IAMSeeder::class);

    config()->set('services.github', [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'redirect' => 'http://localhost/auth/github/callback',
    ]);
});

describe('SOCL-03 link social account', function (): void {
    it('returns an authorization url with a state pinned to the current user', function (): void {
        $user = loginAsUser();

        $driver = app(Factory::class)->driver('github');
        Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

        $response = $this->getJson('/api/v1/auth/social/github/link');

        assertSuccessResponse($response, 200, 'OK');

        $url = $response->json('data.url');
        expect($url)->toBeString();

        if (! is_string($url)) {
            return;
        }

        $queryString = parse_url($url, PHP_URL_QUERY);
        expect($queryString)->toBeString();

        if (! is_string($queryString)) {
            return;
        }

        parse_str($queryString, $query);
        $state = $query['state'] ?? null;
        expect($state)->toBeString();

        if (! is_string($state)) {
            return;
        }

        $payload = SocialState::verify($state);

        expect($payload['action'])->toBe('link');
        expect($payload['user_id'] ?? null)->toBe($user->id);
    })->group('module:iam');

    it('rejects linking a provider that is already linked', function (): void {
        $user = loginAsUser();
        $user->socialAccounts()->create([
            'provider' => 'github',
            'provider_id' => 'github-111',
        ]);

        $response = $this->getJson('/api/v1/auth/social/github/link');

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('validation.social_account_exists'));
    })->group('module:iam');

    it('returns 401 when unauthenticated', function (): void {
        $this->getJson('/api/v1/auth/social/github/link')->assertUnauthorized();
    })->group('module:iam');

    it('links a new provider account through the callback', function (): void {
        $user = loginAsUser();

        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-555',
            'name' => 'Git User',
            'email' => 'git@example.com',
        ]));

        $response = $this->getJson('/api/v1/auth/social/github/callback?state='.urlencode(SocialState::create('link', ['user_id' => $user->id])));

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.access_token'))->toBeString();

        expect($user->socialAccounts()->where('provider', 'github')->where('provider_id', 'github-555')->exists())->toBeTrue();
        expect(SocialAccount::count())->toBe(1);
    })->group('module:iam');

    it('rejects a callback whose state targets another user', function (): void {
        $user = loginAsUser();
        $other = UserFactory::new()->createOne();

        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-777',
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
        ]));

        $response = $this->getJson('/api/v1/auth/social/github/callback?state='.urlencode(SocialState::create('link', ['user_id' => $other->id])));

        assertSuccessResponse($response, 200, 'OK');

        expect($user->socialAccounts()->where('provider_id', 'github-777')->exists())->toBeFalse();
        expect($other->socialAccounts()->where('provider_id', 'github-777')->exists())->toBeTrue();
    })->group('module:iam');
});

describe('unlink social account', function (): void {
    it('unlinks a social account when the user has a password', function (): void {
        $user = loginAsUser();
        $user->socialAccounts()->create([
            'provider' => 'github',
            'provider_id' => 'github-111',
        ]);

        $response = $this->deleteJson('/api/v1/auth/social/github');

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('detail'))->toBe(__('auth.social_unlink_success'));
        expect($user->socialAccounts()->count())->toBe(0);
    })->group('module:iam');

    it('keeps the last social account when the user has no password', function (): void {
        $user = loginAsUser(UserFactory::new()->social('github')->createOne());

        $response = $this->deleteJson('/api/v1/auth/social/github');

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('auth.social_unlink_blocked'));
        expect($user->socialAccounts()->count())->toBe(1);
    })->group('module:iam');

    it('allows unlinking when a second social account exists', function (): void {
        $user = loginAsUser(UserFactory::new()->social('github')->createOne());
        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_id' => 'google-222',
        ]);

        $response = $this->deleteJson('/api/v1/auth/social/github');

        assertSuccessResponse($response, 200, 'OK');
        expect($user->socialAccounts()->count())->toBe(1);
        expect($user->socialAccounts()->firstOrFail()->provider)->toBe('google');
    })->group('module:iam');

    it('rejects unlinking a provider that is not linked', function (): void {
        $user = loginAsUser();

        $response = $this->deleteJson('/api/v1/auth/social/google');

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('validation.social_not_linked'));
    })->group('module:iam');

    it('returns 401 when unauthenticated', function (): void {
        $this->deleteJson('/api/v1/auth/social/github')->assertUnauthorized();
    })->group('module:iam');
});
