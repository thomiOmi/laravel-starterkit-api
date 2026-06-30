<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

beforeEach(function () {
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('Social Authentication (SOP Registration)', function () {
    it('redirects to the provider authorization page', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        Socialite::shouldReceive('driver')->with('google')->andReturn(
            Mockery::mock('Laravel\Socialite\Two\AbstractProvider')
                ->shouldReceive('redirect')->andReturn(new RedirectResponse('https://google.com/auth'))
                ->getMock()
        );

        $this->getJson('/api/v1/auth/social/google/redirect')
            ->assertRedirect('https://google.com/auth');
    })->group('v1');

    it('creates a new user via provider callback with correct linkage', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $mockSocialUser = (new SocialiteUser)->map([
            'id' => 'social-123',
            'nickname' => 'johndoe',
            'name' => 'John Doe',
            'email' => 'john@social.com',
            'avatar' => 'https://social.com/avatar.jpg',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn(
            Mockery::mock('Laravel\Socialite\Two\AbstractProvider')
                ->shouldReceive('stateless')->andReturnSelf()
                ->shouldReceive('user')->andReturn($mockSocialUser)
                ->getMock()
        );

        $response = $this->getJson('/api/v1/auth/social/google/callback');

        $response->toBeSuccessResponse()
            ->assertJsonMissing(['password']) // SOP: No leakage
            ->assertJsonPath('data.user.email', 'john@social.com');

        $user = User::where('email', 'john@social.com')->first();
        expect($user->provider)->toBe('google')
            ->and($user->provider_id)->toBe('social-123')
            ->and($user->avatar)->toBe('https://social.com/avatar.jpg')
            ->and($user->password)->toBeNull();
    })->group('v1');
});
