<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\V1\MediaDeleteController;
use Modules\Media\Http\Controllers\V1\MediaFileController;
use Modules\Media\Http\Controllers\V1\MediaListController;
use Modules\Media\Http\Controllers\V1\MediaShowController;
use Modules\Media\Http\Controllers\V1\MediaUploadController;
use Modules\Media\Http\Controllers\V1\MediaVariantController;

// Public streaming endpoint: the signature is the credential. Signed URLs
// are minted through MediaUrlResolver::signed() (also via show ?expires=).
Route::prefix('media')->middleware(['signed', 'throttle:api'])->group(function (): void {
    Route::get('/{media}/file', MediaFileController::class)->name('file');
})->whereUlid(['media']);

// Intentionally no 'verified' middleware: users may manage their own
// uploads (e.g. avatars) before confirming their email address.
Route::prefix('media')->middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function (): void {
    Route::post('/', MediaUploadController::class)
        ->middleware('permission:'.PermissionEnum::MediaCreate->value)
        ->name('upload');

    Route::get('/', MediaListController::class)
        ->middleware('permission:'.PermissionEnum::MediaView->value)
        ->name('index');

    Route::get('/{media}', MediaShowController::class)->name('show');
    Route::get('/{media}/variant', MediaVariantController::class)->name('variant');
    Route::delete('/{media}', MediaDeleteController::class)->name('delete');
})->whereUlid(['media']);
