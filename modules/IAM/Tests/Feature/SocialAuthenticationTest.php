<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

beforeEach(function () {
    Role::create(['name' => 'user', 'guard_name' => 'web']);

});

describe('Social Authentication', function () {
    it('redirects to the provider authorization page', function () {
        Socialite::shouldReceive('driver')->with('google')->andReturn(
            Mockery::mock('Laravel\Socialite\Two\AbstractProvider')
                ->shouldReceive('stateless')->andReturnSelf()
                ->shouldReceive('redirect')->andReturn(new RedirectResponse('https://google.com/auth'))
                ->getMock()
        );

        expect($this->getJson('/api/v1/auth/social/google/redirect'))
            ->toBeSuccessResponse()
            ->assertJsonPath('data.url', 'https://google.com/auth');
    })->group('v1');

    it('creates a new user via provider callback with correct linkage', function () {
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

        expect($response)->toBeSuccessResponse()
            ->assertJsonMissing(['password'])
            ->assertJsonPath('data.user.email', 'john@social.com');

        $user = User::where('email', 'john@social.com')->first();
        expect($user->provider)->toBe('google')
            ->and($user->provider_id)->toBe('social-123')
            ->and($user->avatar)->toBe('https://social.com/avatar.jpg')
            ->and($user->password)->toBeNull();
    })->group('v1');
});
