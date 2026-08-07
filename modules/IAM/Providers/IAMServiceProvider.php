<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\IAM\Http\Middleware\EnsureUserIsActive;

class IAMServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRoutes();
    }

    public function register(): void {}

    protected function configureRoutes(): void
    {
        Route::aliasMiddleware('active', EnsureUserIsActive::class);

        Route::prefix('api/v1')
            ->middleware('api')
            ->name('v1.')
            ->group(base_path('modules/IAM/Routes/V1.php'));
    }
}
