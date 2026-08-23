<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Http\Controllers\V1\PermissionCreateController;
use Modules\IAM\Models\Permission;

covers(PermissionCreateController::class);

describe('POST /api/v1/permissions', function () {
    beforeEach(function () {
        Permission::firstOrCreate(['name' => PermissionEnum::PermissionCreate->value, 'guard_name' => 'sanctum']);
    });

    it('creates a permission', function () {
        $creator = loginAsUser();
        $creator->givePermissionTo(PermissionEnum::PermissionCreate->value);

        $response = $this->postJson('/api/v1/permissions', [
            'name' => 'report.export',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.name'))->toBe('report.export');
    });

    it('rejects duplicate permission names', function () {
        $creator = loginAsUser();
        $creator->givePermissionTo(PermissionEnum::PermissionCreate->value);
        Permission::firstOrCreate(['name' => 'report.export', 'guard_name' => 'sanctum']);

        $response = $this->postJson('/api/v1/permissions', ['name' => 'report.export']);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['name']);
    });
});
