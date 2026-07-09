<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Actions\RegisterAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\RegisterPayload;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'user', 'guard_name' => 'sanctum']);
});

describe('RegisterAction', function () {
    it('creates a user and returns token', function () {
        $action = app(RegisterAction::class);

        $result = $action->handle(
            new RegisterPayload(name: 'New User', email: 'new@example.com', password: 'Password1!', deviceName: 'test-device'),
            ip: '127.0.0.1',
            userAgent: 'Test',
        );

        expect($result)->toHaveKeys(['user', 'access_token', 'token_type']);
        expect($result['user'])->toBeInstanceOf(User::class)
            ->email->toBe('new@example.com');
        expect($result['access_token'])->toBeString();
        expect($result['token_type'])->toBe('Bearer');
    });
});
