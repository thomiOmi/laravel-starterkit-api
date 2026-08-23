<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\UserCreateController;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

covers(UserCreateController::class);

describe('POST /api/v1/users', function () {
    beforeEach(function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserCreate->value, 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('creates a user with the create permission', function () {
        $creator = loginAsUser();
        $creator->givePermissionTo(PermissionEnum::UserCreate->value);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Created User',
            'email' => 'CREATED@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.email'))->toBe('created@example.com')
            ->and($response->json('data.status'))->toBe(UserStatusEnum::Pending->value);
        // Admin-created users start pending until they verify their email.
    });

    it('rejects duplicate emails', function () {
        $creator = loginAsUser();
        $creator->givePermissionTo(PermissionEnum::UserCreate->value);
        UserFactory::new()->createOne(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Dup',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email']);
    });

    it('rejects users without the create permission', function () {
        loginAsUser();

        assertProblemResponse($this->postJson('/api/v1/users', [
            'name' => 'Nope', 'email' => 'nope@example.com', 'password' => 'password123',
            'password_confirmation' => 'password123',
        ]), 403);
    });

    it('rejects unauthenticated requests', function () {
        $this->postJson('/api/v1/users')->assertUnauthorized();
    });
});
