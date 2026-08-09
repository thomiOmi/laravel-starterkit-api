<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;
use Modules\Media\Controllers\V1\MediaDeleteController;
use Modules\Media\Controllers\V1\MediaListController;
use Modules\Media\Controllers\V1\MediaShowController;
use Modules\Media\Controllers\V1\MediaUploadController;

Route::prefix('media')->name('media.')->middleware(['auth:sanctum', 'active', 'verified', 'throttle:api'])->group(function () {
    Route::get('/', MediaListController::class)->middleware('permission:'.PermissionEnum::MediaView->value)->name('index');
    Route::post('/', MediaUploadController::class)->middleware('permission:'.PermissionEnum::MediaCreate->value)->name('create');

    Route::get('/{media}', MediaShowController::class)->name('show');
    Route::delete('/{media}', MediaDeleteController::class)->name('delete');
})->whereUlid(['media']);
