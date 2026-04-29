<?php

declare(strict_types=1);

namespace Modules\Subscription\Routes;

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Controllers\V1\SubscriptionPlanController;
use Modules\Subscription\Controllers\V1\TenantSubscriptionController;

Route::prefix('subscriptions')->group(function () {
    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::apiResource('plans', SubscriptionPlanController::class);
        Route::post('assign', [TenantSubscriptionController::class, 'assign'])->name('assign');
    });
});
