<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class IAMServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRoutes();
    }

    public function register(): void {}

    protected function configureRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->name('api.v1.')
            ->group(base_path('modules/IAM/Routes/V1.php'));
    }
}
