<?php

use App\Providers\ApiDocsProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\PulseServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    ApiDocsProvider::class,
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    PulseServiceProvider::class,
    RouteServiceProvider::class,
    TelescopeServiceProvider::class,
    TenancyServiceProvider::class,
];
