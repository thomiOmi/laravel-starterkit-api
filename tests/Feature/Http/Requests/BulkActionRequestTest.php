<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = loginAsUser();
});

function createBulkRoute(string $name, string $prefix): void
{
    Route::post("{$prefix}/bulk/{action}", fn () => response()->json(['status' => 200]))->name($name);
}

dataset('authorized bulk actions', [
    'user delete' => ['v1.user.bulk.delete', 'api/v1/users', 'delete'],
    'user restore' => ['v1.user.bulk.restore', 'api/v1/users', 'restore'],
    'role delete' => ['v1.role.bulk.delete', 'api/v1/roles', 'delete'],
]);

dataset('bulk actions without permission', [
    'user delete' => ['v1.user.bulk.delete', 'api/v1/users', 'delete'],
    'user restore' => ['v1.user.bulk.restore', 'api/v1/users', 'restore'],
]);

dataset('mismatched bulk actions', [
    'user delete with restore action' => ['v1.user.bulk.delete', 'api/v1/users', 'delete', 'restore'],
    'role delete with restore action' => ['v1.role.bulk.delete', 'api/v1/roles', 'delete', 'restore'],
]);

it('authorizes bulk action when the user has the matching permission', function (string $routeName, string $prefix, string $action): void {
    createBulkRoute($routeName, $prefix);
    $this->user->assignRole(RoleEnum::Admin->value);

    $response = $this->postJson("/{$prefix}/bulk/{$action}", [
        'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
        'action' => $action,
    ]);

    assertSuccessResponse($response, 200);
})->with('authorized bulk actions');

it('denies bulk action when the user lacks the permission', function (string $routeName, string $prefix, string $action): void {
    createBulkRoute($routeName, $prefix);

    $response = $this->postJson("/{$prefix}/bulk/{$action}", [
        'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
        'action' => $action,
    ]);

    assertProblemResponse($response, 403);
})->with('bulk actions without permission');

it('denies bulk action when the action does not match the route action', function (string $routeName, string $prefix, string $routeAction, string $bodyAction): void {
    createBulkRoute($routeName, $prefix);
    $this->user->assignRole(RoleEnum::Admin->value);

    $response = $this->postJson("/{$prefix}/bulk/{$routeAction}", [
        'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
        'action' => $bodyAction,
    ]);

    assertProblemResponse($response, 403);
})->with('mismatched bulk actions');

describe('bulk action validation', function () {

    it('requires ids as array with min 1 max 50 items', function () {
        createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
        $this->user->assignRole(RoleEnum::Admin->value);

        $response = $this->postJson('/api/v1/users/bulk/delete', ['ids' => []]);

        assertProblemResponse($response, 422);
    });

    it('requires each id to be a ulid string', function () {
        createBulkRoute('v1.user.bulk.delete', 'api/v1/users');
        $this->user->assignRole(RoleEnum::Admin->value);

        $response = $this->postJson('/api/v1/users/bulk/delete', [
            'ids' => ['invalid-id'],
        ]);

        assertProblemResponse($response, 422);
    });

});
