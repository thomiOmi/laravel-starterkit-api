<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\IAM\Actions\RegisterAction;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Payloads\V1\RegisterPayload;

covers(RegisterAction::class);

pest()->use(RefreshDatabase::class);

describe('RegisterAction', function () {
    beforeEach(function () {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }

        Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('creates user, assigns User role and fires Registered event', function () {
        Event::fake([Registered::class]);

        $payload = new RegisterPayload(name: 'Jane Doe', email: 'jane@example.com', password: 'password123', deviceName: 'iPhone');

        $result = app(RegisterAction::class)->handle($payload, '127.0.0.1', 'TestAgent');

        expect($result['user']->email)->toBe('jane@example.com')
            ->and($result['user']->hasRole(RoleEnum::User->value))->toBeTrue()
            ->and($result['access_token'])->not->toBeEmpty()
            ->and($result['token_type'])->toBe('Bearer');

        Event::assertDispatched(Registered::class, fn (Registered $event) => $event->user->is($result['user']));
    });

    it('handles payload without device name', function () {
        $payload = new RegisterPayload(name: 'John', email: 'john2@example.com', password: 'password123');

        $result = app(RegisterAction::class)->handle($payload);

        expect($result['user']->email)->toBe('john2@example.com')
            ->and($result['access_token'])->not->toBeEmpty();
    });
});
