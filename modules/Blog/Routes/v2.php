<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Controllers\V2\BlogController;

Route::get('blogs', [BlogController::class, 'index'])->name('blogs.index');
