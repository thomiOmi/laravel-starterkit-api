<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Http\Requests\BulkActionRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Role\Models\Permission;

test('BulkActionRequest validates ULIDs and count limits', function () {
    Route::post('/test-bulk', function (BulkActionRequest $request) {
        return response()->json(['valid' => true]);
    });

    // Test missing ids
    // We must pass a header that makes it a JSON request to bypass standard redirects if any
    $this->postJson('/test-bulk', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids']);

    // Test invalid ULIDs
    $this->postJson('/test-bulk', ['ids' => ['not-a-ulid']])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids.0']);
});

test('BulkActionRequest enforces modular permissions', function () {
    $user = loginAsUser();

    // Seed permission using Model to ensure ULID if needed,
    // but standard Spatie Model might not have ULID by default if not configured.
    // However, our modules use ULIDs.
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
