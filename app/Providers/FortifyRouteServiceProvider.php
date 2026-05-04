<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::ignoreRoutes();
    }
}
