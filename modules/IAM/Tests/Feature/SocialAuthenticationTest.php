<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Enums\RoleEnum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

beforeEach(function () {
    Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
});

describe('Social Authentication', function () {
    it('redirects to the provider authorization page', function () {
        Socialite::fake('google');

        expect($this->getJson('/api/v1/auth/social/google/redirect'))
            ->toBeSuccessResponse()
            ->assertJsonPath('data.url', 'https://socialite.fake/google/authorize');
    })->group('v1');

    it('creates a new user via provider callback with correct linkage', function () {
        Socialite::fake(
            'google',
            SocialiteUser::fake([
                'id' => 'social-123',
                'nickname' => 'johndoe',
                'name' => 'John Doe',
                'email' => 'john@social.com',
                'avatar' => 'https://social.com/avatar.jpg',
            ]),
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
