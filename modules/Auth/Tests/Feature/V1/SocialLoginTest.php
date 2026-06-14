<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Social Redirect', function () {
    it('returns redirect URL for valid provider', function () {
        $this->withoutExceptionHandling();

        $providerMock = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $providerMock->shouldReceive('stateless')->andReturnSelf();
        $providerMock->shouldReceive('redirect')
            ->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($providerMock);

        $response = $this->getJson('/api/v1/auth/social/google/redirect');

        $response->assertSuccessful()
            ->assertJsonStructure(['data' => ['url']]);
    });

    it('returns 422 for invalid provider', function () {
        $response = $this->getJson('/api/v1/auth/social/twitter/redirect');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    });
});

describe('Social Callback', function () {
    it('creates a new user and returns token', function () {
        $socialUser = new SocialUser;
        $socialUser->id = 'google-123';
        $socialUser->name = 'John Doe';
        $socialUser->email = 'john@example.com';
        $socialUser->token = 'mock-token';
        $socialUser->refreshToken = 'mock-refresh';
        $socialUser->expiresIn = 3600;

        $providerMock = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $providerMock->shouldReceive('stateless')->andReturnSelf();
        $providerMock->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($providerMock);

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertSuccessful()
            ->assertJsonPath('message', __('auth.social_login_success'))
            ->assertJsonStructure(['data' => ['user', 'access_token', 'token_type']]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    });

    it('logs in existing linked user', function () {
        $user = User::factory()->create([
            'provider' => 'google',
            'provider_id' => 'existing-123',
        ]);

        $socialUser = new SocialUser;
        $socialUser->id = 'existing-123';
        $socialUser->name = 'Existing User';
        $socialUser->email = $user->email;
        $socialUser->token = 'mock-token';

        $providerMock = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $providerMock->shouldReceive('stateless')->andReturnSelf();
        $providerMock->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($providerMock);

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertSuccessful()
            ->assertJsonPath('message', __('auth.social_login_success'))
            ->assertJsonStructure(['data' => ['user', 'access_token', 'token_type']]);
    });

    it('links provider to existing email user', function () {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'provider' => null,
            'provider_id' => null,
        ]);

        $socialUser = new SocialUser;
        $socialUser->id = 'new-link-123';
        $socialUser->name = 'Linked User';
        $socialUser->email = 'existing@example.com';
        $socialUser->token = 'mock-token';

        $providerMock = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $providerMock->shouldReceive('stateless')->andReturnSelf();
        $providerMock->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($providerMock);

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertSuccessful();

        $user->refresh();
        expect($user->provider)->toBe('google');
        expect($user->provider_id)->toBe('new-link-123');
    });

    it('returns 422 for invalid provider callback', function () {
        $response = $this->getJson('/api/v1/auth/social/twitter/callback');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    });
});
