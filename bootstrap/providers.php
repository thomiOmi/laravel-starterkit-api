<?php

use App\Providers\ApiDocsProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    RouteServiceProvider::class,
    ApiDocsProvider::class,
    TenancyServiceProvider::class,
];
