<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Http\Controllers\V1\RoleCreateController;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

covers(RoleCreateController::class);

describe('POST /api/v1/roles', function () {
    beforeEach(function () {
        Permission::firstOrCreate(['name' => PermissionEnum::RoleCreate->value, 'guard_name' => 'sanctum']);
    });

    it('creates a role with the create permission', function () {
        $creator = loginAsUser();
        $creator->givePermissionTo(PermissionEnum::RoleCreate->value);

        $response = $this->postJson('/api/v1/roles', [
            'name' => 'editor',
            'description' => 'Can edit content',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.name'))->toBe('editor')
            ->and($response->json('data.description'))->toBe('Can edit content');
    });

    it('rejects duplicate role names', function () {
        $creator = loginAsUser();
        $creator->givePermissionTo(PermissionEnum::RoleCreate->value);
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'sanctum']);

        $response = $this->postJson('/api/v1/roles', ['name' => 'editor']);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['name']);
    });

    it('rejects users without the create permission', function () {
        loginAsUser();

        assertProblemResponse($this->postJson('/api/v1/roles', ['name' => 'nope']), 403);
    });
});
