<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AuditLog\Controllers\V1\AuditLogController;

Route::middleware(['auth:sanctum,tenancy.request', 'can:audit.view'])->group(function () {
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });
});
