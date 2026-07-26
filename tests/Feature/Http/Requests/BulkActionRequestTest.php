<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Database\Seeders\RoleSeeder;
use Modules\IAM\Models\User;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createBulkRoute(string $name, string $prefix): void
{
    Route::post("{$prefix}/bulk/{action}", function () {
        return response()->json(['status' => 200]);
    })->name($name);
}

// ---------- User bulk delete ----------

test('user bulk delete authorizes with UserDelete permission', function () {
    createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/users/bulk/delete', [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV', '01ARZ3NDEKTSV4RRFFQ69G5FAW'],
            'action' => 'delete',
        ]);

    expect($response->getStatusCode())->toBe(200);
});

test('user bulk delete denies without UserDelete permission', function () {
    createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/users/bulk/delete', [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
        ]);

    $response->assertStatus(403);
});

// ---------- User bulk restore ----------

test('user bulk restore authorizes with UserRestore permission', function () {
    createBulkRoute('v1.user.bulk.restore', 'api/v1/users');
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/users/bulk/restore', [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => 'restore',
        ]);

    expect($response->getStatusCode())->toBe(200);
});

test('user bulk restore denies without UserRestore permission', function () {
    createBulkRoute('v1.user.bulk.restore', 'api/v1/users');
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/users/bulk/restore', [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
        ]);

    $response->assertStatus(403);
});

// ---------- Role bulk delete ----------

test('role bulk delete authorizes with RoleDelete permission', function () {
    createBulkRoute('v1.role.bulk.delete', 'api/v1/roles');
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/roles/bulk/delete', [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => 'delete',
        ]);

    expect($response->getStatusCode())->toBe(200);
});

test('role bulk delete denies role restore action', function () {
    createBulkRoute('v1.role.bulk.delete', 'api/v1/roles');
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/roles/bulk/delete', [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => 'restore',
        ]);

    $response->assertStatus(403);
});

// ---------- Validation ----------

test('requires ids as array with min 1 max 50 items', function () {
    createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/users/bulk/delete', ['ids' => []]);

    $response->assertStatus(422);
});

test('requires each id to be a ulid string', function () {
    createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/users/bulk/delete', [
            'ids' => ['invalid-id'],
        ]);

    $response->assertStatus(422);
});

test('denies action that does not match route action', function () {
    createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/users/bulk/delete', [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => 'restore',
        ]);

    $response->assertStatus(403);
});
