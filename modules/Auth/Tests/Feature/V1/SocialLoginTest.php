<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialUser;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Social Redirect', function () {
    it('returns redirect URL for valid provider', function () {
        Socialite::fake('google');

        $response = $this->getJson('/api/v1/auth/social/google/redirect');

        $response->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data.url')
                ->etc()
            );
    });

    it('returns 400 for invalid provider', function () {
        $response = $this->getJson('/api/v1/auth/social/twitter/redirect');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    });
});

describe('Social Callback', function () {
    it('creates a new user and returns token', function () {
        Socialite::fake('google', SocialUser::fake([
            'id' => 'google-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]));

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertSuccessful()
            ->assertJsonPath('detail', __('auth.social_login_success'))
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data.user')
                ->has('data.access_token')
                ->where('data.token_type', 'Bearer')
                ->etc()
            );

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
    });

    it('logs in existing linked user', function () {
        User::factory()->create([
            'provider' => 'google',
            'provider_id' => 'existing-123',
        ]);

        Socialite::fake('google', SocialUser::fake([
            'id' => 'existing-123',
            'name' => 'Existing User',
            'email' => 'existing@example.com',
        ]));

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertSuccessful()
            ->assertJsonPath('detail', __('auth.social_login_success'))
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data.user')
                ->has('data.access_token')
                ->where('data.token_type', 'Bearer')
                ->etc()
            );
    });

    it('links provider to existing email user', function () {
        User::factory()->create([
            'email' => 'existing@example.com',
            'provider' => null,
            'provider_id' => null,
        ]);

        Socialite::fake('google', SocialUser::fake([
            'id' => 'new-link-123',
            'name' => 'Linked User',
            'email' => 'existing@example.com',
            'avatar' => 'https://example.com/linked-avatar.jpg',
        ]));

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'existing@example.com',
            'provider' => 'google',
            'provider_id' => 'new-link-123',
            'avatar' => 'https://example.com/linked-avatar.jpg',
        ]);
    });

    it('returns 400 for denied authorization', function () {
        Socialite::fake('google', function () {
            throw new InvalidStateException;
        });

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('detail', __('auth.social_denied'));
    });

    it('returns 400 for invalid provider callback', function () {
        $response = $this->getJson('/api/v1/auth/social/twitter/callback');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    });
});
