<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Health\Controllers\V1\HealthController;

Route::get('health', [HealthController::class, 'check'])->name('check');
