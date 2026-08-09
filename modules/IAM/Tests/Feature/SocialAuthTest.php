<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Support\Carbon;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IAM\Actions\SocialState;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\User;

beforeEach(function (): void {
    $this->seed(IAMSeeder::class);

    config()->set('services.google', [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'redirect' => 'http://localhost/auth/google/callback',
    ]);
});

describe('SOCL-01 social redirect', function (): void {
    it('returns an authorization url carrying a signed state token', function (): void {
        $driver = app(Factory::class)->driver('google');
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->getJson('/api/v1/auth/social/google/redirect');

        assertSuccessResponse($response, 200, 'OK');

        $url = $response->json('data.url');
        expect($url)->toBeString();

        if (! is_string($url)) {
            return;
        }

        expect(parse_url($url, PHP_URL_HOST))->toBe('accounts.google.com');

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
        expect($payload['action'])->toBe('login');
    })->group('module:iam', 'smoke');
});

describe('SOCL-02 social callback login', function (): void {
    it('creates a user and links the social account on first login', function (): void {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-12345',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'avatar' => null,
        ]));

        $response = $this->getJson('/api/v1/auth/social/google/callback?state='.urlencode(SocialState::create('login')));

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('detail'))->toBe(__('auth.social_login_success'));
        expect($response->json('data.access_token'))->toBeString();

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        expect($user->hasRole(RoleEnum::User->value))->toBeTrue();
        expect($user->email_verified_at)->not->toBeNull();
        expect($user->socialAccounts()->where('provider', 'google')->where('provider_id', 'google-12345')->exists())->toBeTrue();
    })->group('module:iam', 'smoke');

    it('logs in an existing user whose verified email matches', function (): void {
        $user = UserFactory::new()->createOne([
            'email' => 'bob@example.com',
            'email_verified_at' => now(),
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-54321',
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]));

        $response = $this->getJson('/api/v1/auth/social/google/callback?state='.urlencode(SocialState::create('login')));

        assertSuccessResponse($response, 200, 'OK');

        expect(User::where('email', 'bob@example.com')->count())->toBe(1);
        expect($user->refresh()->socialAccounts()->where('provider_id', 'google-54321')->exists())->toBeTrue();
    })->group('module:iam');

    it('binds and verifies an existing unverified account with the same email', function (): void {
        $user = UserFactory::new()->createOne([
            'email' => 'carol@example.com',
            'email_verified_at' => null,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-11111',
            'name' => 'Carol',
            'email' => 'carol@example.com',
        ]));

        $response = $this->getJson('/api/v1/auth/social/google/callback?state='.urlencode(SocialState::create('login')));

        assertSuccessResponse($response, 200, 'OK');

        expect(User::where('email', 'carol@example.com')->count())->toBe(1);

        $user->refresh();

        expect($user->email_verified_at)->not->toBeNull();
        expect($user->socialAccounts()->where('provider_id', 'google-11111')->exists())->toBeTrue();
    })->group('module:iam');

    it('falls back to a synthetic email when the provider sends none', function (): void {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => '22222',
            'name' => 'No Email',
            'email' => null,
        ]));

        $response = $this->getJson('/api/v1/auth/social/google/callback?state='.urlencode(SocialState::create('login')));

        assertSuccessResponse($response, 200, 'OK');

        expect(User::where('email', 'google-22222@social.local')->exists())->toBeTrue();
    })->group('module:iam');

    it('rejects an invalid state token', function (): void {
        Socialite::fake('google', SocialiteUser::fake());

        $response = $this->getJson('/api/v1/auth/social/google/callback?state='.urlencode('not-a-valid-state'));

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('validation.social_state_invalid'));
    })->group('module:iam');

    it('rejects an expired state token', function (): void {
        Socialite::fake('google', SocialiteUser::fake());

        $state = SocialState::create('login');
        Carbon::setTestNow(now()->addMinutes(11));

        $response = $this->getJson('/api/v1/auth/social/google/callback?state='.urlencode($state));

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('validation.social_state_expired'));
    })->group('module:iam');

    it('rejects an unsupported provider', function (): void {
        $response = $this->getJson('/api/v1/auth/social/facebook/callback?state='.urlencode(SocialState::create('login')));

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('validation.social_provider_invalid'));
    })->group('module:iam');

    afterEach(function (): void {
        Carbon::setTestNow();
    });
});
