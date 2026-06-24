<?php

declare(strict_types=1);

use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Payloads\V1\UserPayload;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('CreateUserAction', function () {
    it('creates a user', function () {
        $password = config('auth.default_password');
        $payload = new UserPayload(
            name: 'John Doe',
            email: 'john@example.com',
            password: $password,
        );

        $action = app(CreateUserAction::class);
        $user = $action->handle($payload);

        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($user->hasRole('user'))->toBeTrue();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    it('creates a user without password for optional field', function () {
        $payload = new UserPayload(
            name: 'Jane Doe',
            email: 'jane@example.com',
        );

        $action = app(CreateUserAction::class);
        $user = $action->handle($payload);

        expect($user->name)->toBe('Jane Doe');
        expect($user->email)->toBe('jane@example.com');
        expect($user->hasRole('user'))->toBeTrue();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    });
});
