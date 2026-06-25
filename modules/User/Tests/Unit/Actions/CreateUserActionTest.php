<?php

declare(strict_types=1);

use Modules\User\Actions\CreateUserAction;
use Modules\User\Payloads\V1\UserPayload;

describe('CreateUserAction', function () {
    beforeEach(function () {
        \Modules\Role\Models\Role::factory()->create(['name' => 'user']);
    });

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

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    });
});
