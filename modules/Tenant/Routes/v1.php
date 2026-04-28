<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Controllers\V1\TenantController;

Route::middleware(['throttle:api'])->group(function () {
    Route::get('tenants', [TenantController::class, 'index'])->name('index');
    Route::post('tenants', [TenantController::class, 'store'])->name('store');
    Route::get('tenants/{id}', [TenantController::class, 'show'])->name('show');
    Route::delete('tenants/{id}', [TenantController::class, 'destroy'])->name('destroy');
});
