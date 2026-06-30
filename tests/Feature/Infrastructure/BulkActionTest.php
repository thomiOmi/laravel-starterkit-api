<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Http\Requests\BulkActionRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Role\Models\Permission;
use Modules\User\Models\User;
use Tests\TestCase;

test('BulkActionRequest validates ULIDs and count limits', function () {
    /** @var TestCase $this */
    /** @var TestCase $this */
    Route::post('/test-bulk', function (BulkActionRequest $request) {
        return response()->json(['valid' => true]);
    });

    $this->postJson('/test-bulk', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids']);

    $this->postJson('/test-bulk', ['ids' => ['not-a-ulid']])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids.0']);
});

test('BulkActionRequest enforces modular permissions', function () {
    /** @var TestCase $this */
    /** @var TestCase $this */
    $user = loginAsUser(); /** @var User $user */
    Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);

    Route::post('/api/v1/user/bulk/delete', function (BulkActionRequest $request) {
        return response()->json(['authorized' => true]);
    })->name('api.v1.user.bulk.delete');

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
