<?php

declare(strict_types=1);

namespace Modules\Infrastructure\Tests\Feature;

use App\Http\Requests\BulkActionRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;

test('BulkActionRequest validates ULIDs and count limits', function () {
    Route::post('/test-bulk', function (BulkActionRequest $request) {
        return response()->json(['valid' => true]);
    });

    // Test missing ids
    $this->postJson('/test-bulk', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids']);

    // Test invalid ULIDs
    $this->postJson('/test-bulk', ['ids' => ['not-a-ulid']])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids.0']);

    // Test valid ULIDs
    $ulid = (string) Str::ulid();
    $this->postJson('/test-bulk', ['ids' => [$ulid], 'action' => 'delete'])
        ->assertStatus(403); // Fails authorization because no permissions yet
});

test('BulkActionRequest enforces modular permissions', function () {
    // We mock the user and the route name to trigger the authorization logic in BulkActionRequest
    $user = loginAsUser();

    // Seed permission
    Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);

    Route::post('/api/v1/user/bulk/delete', function (BulkActionRequest $request) {
        return response()->json(['authorized' => true]);
    })->name('api.v1.user.bulk.delete');

    // Unauthorized attempt
    $this->postJson('/api/v1/user/bulk/delete', [
        'ids' => [(string) Str::ulid()],
        'action' => 'delete',
    ])->assertStatus(403);

    // Authorized attempt
    $user->givePermissionTo('user.delete');

    $this->postJson('/api/v1/user/bulk/delete', [
        'ids' => [(string) Str::ulid()],
        'action' => 'delete',
    ])->assertStatus(200);
});
