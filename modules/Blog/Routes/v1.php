<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Controllers\V1\BlogController;

Route::get('blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('blogs/{blog}', [BlogController::class, 'show'])->name('blogs.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('blogs', [BlogController::class, 'store'])->name('blogs.store');
});
