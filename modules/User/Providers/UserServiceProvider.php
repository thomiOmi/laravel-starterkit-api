<?php

namespace Modules\User\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
