<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Actions\RegisterAction;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\RegisterPayload;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

covers(RegisterAction::class);

describe('RegisterAction', function (): void {
    beforeEach(function (): void {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }

        Role::query()->firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('creates user, assigns User role and fires Registered event', function (): void {
        Event::fake([Registered::class]);

        $payload = new RegisterPayload(name: 'Jane Doe', email: 'jane@example.com', password: 'password123', deviceName: 'iPhone');

        $result = resolve(RegisterAction::class)->handle($payload, '127.0.0.1', 'TestAgent');

        expect($result['user']->email)->toBe('jane@example.com')
            ->and($result['user']->hasRole(RoleEnum::User->value))->toBeTrue()
            ->and($result['access_token'])->not->toBeEmpty()
            ->and($result['token_type'])->toBe('Bearer')
            ->and($result)->toHaveKeys(['user', 'access_token', 'token_type', 'expires_at', 'expires_in']);

        Event::assertDispatched(fn (Registered $event) => $event->user->is($result['user']));
        Event::assertDispatchedTimes(Registered::class, 1);
    });

    it('fires Registered after the transaction has been committed', function (): void {
        Notification::fake();

        $baselineTransactionLevel = DB::transactionLevel();
        $observed = null;

        Event::listen(Registered::class, function (Registered $event) use (&$observed): void {
            $observed = [
                'transaction_level' => DB::transactionLevel(),
                'user_is_committed' => User::query()->whereKey($event->user->getKey())->exists(),
            ];
        });

        $payload = new RegisterPayload(name: 'Order Check', email: 'order-check@example.com', password: 'password123');

        resolve(RegisterAction::class)->handle($payload);

        expect($observed)->toBe([
            'transaction_level' => $baselineTransactionLevel,
            'user_is_committed' => true,
        ]);
    });

    it('rolls back the user when access token creation fails', function (): void {
        Event::fake([Registered::class]);

        PersonalAccessToken::creating(function (): void {
            throw new RuntimeException('Simulated token persistence failure.');
        });

        try {
            $payload = new RegisterPayload(name: 'Token Failure', email: 'token-failure@example.com', password: 'password123');

            expect(fn () => resolve(RegisterAction::class)->handle($payload))
                ->toThrow(RuntimeException::class)
                ->and(User::query()->where('email', 'token-failure@example.com')->exists())->toBeFalse();

            Event::assertNotDispatched(Registered::class);
        } finally {
            PersonalAccessToken::flushEventListeners();
        }
    });

    it('handles payload without device name', function (): void {
        $payload = new RegisterPayload(name: 'John', email: 'john2@example.com', password: 'password123');

        $result = resolve(RegisterAction::class)->handle($payload);

        expect($result['user']->email)->toBe('john2@example.com')
            ->and($result['access_token'])->not->toBeEmpty();
    });
});

describe('RegisterAction without the User role', function (): void {
    it('rolls back the user when role assignment fails', function (): void {
        Event::fake([Registered::class]);

        $payload = new RegisterPayload(name: 'No Role', email: 'no-role@example.com', password: 'password123');

        expect(fn () => resolve(RegisterAction::class)->handle($payload))
            ->toThrow(RoleDoesNotExist::class)
            ->and(User::query()->where('email', 'no-role@example.com')->exists())->toBeFalse();

        Event::assertNotDispatched(Registered::class);
    });
});
