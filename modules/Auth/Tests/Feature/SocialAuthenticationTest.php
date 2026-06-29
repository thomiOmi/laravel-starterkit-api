<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse;

beforeEach(function () {
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('Social Authentication', function () {
    it('redirects to provider', function () {
        Socialite::shouldReceive('driver')->with('github')->andReturn(
            Mockery::mock('Laravel\Socialite\Two\AbstractProvider')
                ->shouldReceive('redirect')->andReturn(new RedirectResponse('https://github.com/auth'))
                ->getMock()
        );

        $this->getJson('/api/v1/auth/social/github/redirect')
            ->assertRedirect('https://github.com/auth');
    })->group('v1');

    it('authenticates via callback', function () {
        $mockUser = (new SocialiteUser)->map([
            'id' => '123',
            'nickname' => 'jules',
            'name' => 'Jules',
            'email' => 'jules@social.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        Socialite::shouldReceive('driver')->with('github')->andReturn(
            Mockery::mock('Laravel\Socialite\Two\AbstractProvider')
                ->shouldReceive('stateless')->andReturnSelf()
                ->shouldReceive('user')->andReturn($mockUser)
                ->getMock()
        );

        $this->getJson('/api/v1/auth/social/github/callback')
            ->toBeSuccessResponse()
            ->assertJsonPath('data.user.email', 'jules@social.com');

        expect(User::where('provider_id', '123')->exists())->toBeTrue();
    })->group('v1');
});
