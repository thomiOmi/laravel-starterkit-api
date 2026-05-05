<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('social redirect returns a redirect response for valid provider', function () {
    $response = $this->getJson(route('api.v1.auth.social.redirect', ['provider' => 'github']));
    $response->assertStatus(302);
    $this->assertStringContainsString('github.com/login/oauth/authorize', $response->getTargetUrl());
});

test('social callback creates a new user and returns token', function () {
    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('12345');
    $abstractUser->shouldReceive('getEmail')->andReturn('social@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Social User');
    $abstractUser->shouldReceive('getNickname')->andReturn('socialuser');

    $provider = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $provider->shouldReceive('stateless')->andReturn($provider);
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->getJson(route('api.v1.auth.social.callback', ['provider' => 'github']));
    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'data' => ['user', 'access_token', 'token_type']]);

    $this->assertDatabaseHas('users', ['email' => 'social@example.com', 'provider' => 'github', 'provider_id' => '12345']);
});

test('social callback updates existing user if email already exists', function () {
    $user = User::factory()->create(['email' => 'existing@example.com', 'name' => 'Existing User']);
    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('123456');
    $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('Social Name');
    $abstractUser->shouldReceive('getNickname')->andReturn('socialname');

    $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
    $provider->shouldReceive('stateless')->andReturn($provider);
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->getJson(route('api.v1.auth.social.callback', ['provider' => 'google']));
    $response->assertStatus(200);

    $user->refresh();
    expect($user->provider)->toBe('google');
    expect($user->provider_id)->toBe('123456');
});

test('social callback fails gracefully with invalid OAuth response', function () {
    Socialite::shouldReceive('driver')->andThrow(new Exception('Invalid token'));
    $response = $this->getJson(route('api.v1.auth.social.callback', ['provider' => 'github']));
    $response->assertStatus(422)->assertJsonPath('status', 'error');
});

test('social callback rejects unsupported provider', function () {
    $response = $this->getJson('/api/v1/auth/social/facebook/callback');
    $response->assertStatus(404);
});
