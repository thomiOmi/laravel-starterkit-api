<?php

declare(strict_types=1);

namespace Modules\Webhook\Routes;

use Illuminate\Support\Facades\Route;
use Modules\Webhook\Controllers\V1\WebhookController;

Route::prefix('webhooks')->middleware(['auth:sanctum', 'throttle:api', 'plan.feature:webhooks'])->group(function () {
    Route::get('/', [WebhookController::class, 'index'])->name('index');
    Route::post('/', [WebhookController::class, 'store'])->name('store');
    Route::get('/{id}', [WebhookController::class, 'show'])->name('show');
    Route::delete('/{id}', [WebhookController::class, 'destroy'])->name('destroy');
});
