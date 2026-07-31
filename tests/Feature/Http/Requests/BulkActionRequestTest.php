<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createBulkRoute(string $name, string $prefix): void
{
    Route::post("{$prefix}/bulk/{action}", fn () => response()->json(['status' => 200]))->name($name);
}

describe('user bulk actions', function () {

    describe('delete', function () {
        it('authorizes with UserDelete permission', function () {
            createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
            $user = UserFactory::new()->createOne();
            $user->assignRole(RoleEnum::Admin->value);

            $response = $this->actingAs($user)
                ->postJson('/api/v1/users/bulk/delete', [
                    'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV', '01ARZ3NDEKTSV4RRFFQ69G5FAW'],
                    'action' => 'delete',
                ]);

            expect($response->getStatusCode())->toBe(200);
        });

        it('denies without UserDelete permission', function () {
            createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
            $user = UserFactory::new()->createOne();

            $response = $this->actingAs($user)
                ->postJson('/api/v1/users/bulk/delete', [
                    'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                ]);

            $response->assertStatus(403);
        });
    });

    describe('restore', function () {
        it('authorizes with UserRestore permission', function () {
            createBulkRoute('v1.user.bulk.restore', 'api/v1/users');
            $user = UserFactory::new()->createOne();
            $user->assignRole(RoleEnum::Admin->value);

            $response = $this->actingAs($user)
                ->postJson('/api/v1/users/bulk/restore', [
                    'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                    'action' => 'restore',
                ]);

            expect($response->getStatusCode())->toBe(200);
        });

        it('denies without UserRestore permission', function () {
            createBulkRoute('v1.user.bulk.restore', 'api/v1/users');
            $user = UserFactory::new()->createOne();

            $response = $this->actingAs($user)
                ->postJson('/api/v1/users/bulk/restore', [
                    'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                ]);

            $response->assertStatus(403);
        });
    });

});

describe('role bulk actions', function () {

    it('authorizes role bulk delete with RoleDelete permission', function () {
        createBulkRoute('v1.role.bulk.delete', 'api/v1/roles');
        $user = UserFactory::new()->createOne();
        $user->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles/bulk/delete', [
                'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                'action' => 'delete',
            ]);

        expect($response->getStatusCode())->toBe(200);
    });

    it('denies role bulk delete when action is restore', function () {
        createBulkRoute('v1.role.bulk.delete', 'api/v1/roles');
        $user = UserFactory::new()->createOne();
        $user->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/roles/bulk/delete', [
                'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                'action' => 'restore',
            ]);

        $response->assertStatus(403);
    });

});

describe('bulk action validation', function () {

    it('requires ids as array with min 1 max 50 items', function () {
        createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
        $user = UserFactory::new()->createOne();
        $user->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users/bulk/delete', ['ids' => []]);

        $response->assertStatus(422);
    });

    it('requires each id to be a ulid string', function () {
        createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
        $user = UserFactory::new()->createOne();
        $user->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users/bulk/delete', [
                'ids' => ['invalid-id'],
            ]);

        $response->assertStatus(422);
    });

    it('denies when action does not match route action', function () {
        createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
        $user = UserFactory::new()->createOne();
        $user->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/users/bulk/delete', [
                'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                'action' => 'restore',
            ]);

        $response->assertStatus(403);
    });

});
