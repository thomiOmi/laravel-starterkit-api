<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Modules\IAM\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = loginAsUser();
});

dataset('authorized bulk actions', [
    'user delete' => ['api/v1/users', 'delete'],
    'user restore' => ['api/v1/users', 'restore'],
    'role delete' => ['api/v1/roles', 'delete'],
]);

dataset('bulk actions without permission', [
    'user delete' => ['api/v1/users', 'delete'],
    'user restore' => ['api/v1/users', 'restore'],
]);

dataset('mismatched bulk actions', [
    'user delete with restore action' => ['api/v1/users', 'delete', 'restore'],
    'role delete with restore action' => ['api/v1/roles', 'delete', 'restore'],
]);

describe('bulk authorization', function () {
    it('authorizes bulk action when the user has the matching permission', function (string $prefix, string $action): void {
        $this->user->assignRole(RoleEnum::Admin->value);

        $response = $this->postJson("/{$prefix}/bulk/{$action}", [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => $action,
        ]);

        assertSuccessResponse($response, 200);
    })->with('authorized bulk actions')->group('module:iam');

    it('denies bulk action when the user lacks the permission', function (string $prefix, string $action): void {
        $response = $this->postJson("/{$prefix}/bulk/{$action}", [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => $action,
        ]);

        assertProblemResponse($response, 403);
    })->with('bulk actions without permission')->group('module:iam');

    it('denies bulk action when the action does not match the route action', function (string $prefix, string $routeAction, string $bodyAction): void {
        $this->user->assignRole(RoleEnum::Admin->value);

        $response = $this->postJson("/{$prefix}/bulk/{$routeAction}", [
            'ids' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'action' => $bodyAction,
        ]);

        assertProblemResponse($response, 403);
    })->with('mismatched bulk actions')->group('module:iam');
});

describe('bulk action validation', function () {

    it('requires ids as array with min 1 max 50 items', function () {
        $this->user->assignRole(RoleEnum::Admin->value);

        $response = $this->postJson('/api/v1/users/bulk/delete', ['ids' => []]);

        assertProblemResponse($response, 422);
    })->group('module:iam');

    it('requires each id to be a ulid string', function () {
        $this->user->assignRole(RoleEnum::Admin->value);

        $response = $this->postJson('/api/v1/users/bulk/delete', [
            'ids' => ['invalid-id'],
        ]);

        assertProblemResponse($response, 422);
    })->group('module:iam');

});
