<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Http\Requests\BulkActionRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\IAM\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

test('BulkActionRequest validates ULIDs and count limits', function () {
    Route::post('/test-bulk', function (BulkActionRequest $request) {
        return response()->json(['valid' => true]);
    })->name('v1.user.bulk.delete');

    $user = loginAsUser();
    Permission::firstOrCreate(['name' => 'user.delete', 'guard_name' => 'sanctum']);
    $user->givePermissionTo('user.delete');

    $this->postJson('/test-bulk', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids']);

    $this->postJson('/test-bulk', ['ids' => ['not-a-ulid']])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids.0']);
});

test('BulkActionRequest enforces modular permissions', function () {
    $user = loginAsUser();

    Permission::firstOrCreate(['name' => 'user.delete', 'guard_name' => 'sanctum']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Route::post('/api/v1/user/bulk/delete', function (BulkActionRequest $request) {
        return response()->json(['authorized' => true]);
    })->name('v1.user.bulk.delete');

    $this->postJson('/api/v1/user/bulk/delete', [
        'ids' => [(string) Str::ulid()],
        'action' => 'delete',
    ])->assertStatus(403);

    $user->givePermissionTo('user.delete');

    $this->postJson('/api/v1/user/bulk/delete', [
        'ids' => [(string) Str::ulid()],
        'action' => 'delete',
    ])->assertStatus(200);
});
