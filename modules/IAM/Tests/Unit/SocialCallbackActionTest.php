<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Modules\IAM\Actions\SocialCallbackAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
});

describe('SocialCallbackAction', function () {
    it('creates a new user via social login', function () {
        Socialite::fake('google', SocialUser::fake([
            'id' => '12345',
            'name' => 'Social User',
            'email' => 'social@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ]));

        $action = app(SocialCallbackAction::class);
        $result = $action->handle('google', '127.0.0.1', 'Test');

        expect($result)->toHaveKeys(['user', 'access_token', 'token_type']);
        expect($result['user']->email)->toBe('social@example.com');
        expect($result['user']->provider)->toBe('google');
        expect($result['user']->provider_id)->toBe('12345');
    });

    it('finds and updates existing user by email', function () {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        Socialite::fake('google', SocialUser::fake([
            'id' => '67890',
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'avatar' => 'https://example.com/new-avatar.png',
        ]));

        $action = app(SocialCallbackAction::class);
        $result = $action->handle('google', '127.0.0.1', 'Test');

        expect($result['user']->id)->toBe($user->id);
        expect($result['user']->provider)->toBe('google');
        expect($result['user']->provider_id)->toBe('67890');
    });

    it('throws InvalidArgumentException for an invalid provider', function () {
        $action = app(SocialCallbackAction::class);

        $action->handle('facebook', '127.0.0.1', 'Test');
    })->throws(InvalidArgumentException::class);
});
