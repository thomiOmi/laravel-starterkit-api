<?php

use App\Providers\AppServiceProvider;
use App\Providers\ModuleLoaderServiceProvider;
use App\Providers\RouteServiceProvider;

return [
    AppServiceProvider::class,
    ModuleLoaderServiceProvider::class,
    RouteServiceProvider::class,
];
