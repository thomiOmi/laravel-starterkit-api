<?php

use App\Providers\ApiDocsProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\RouteServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    RouteServiceProvider::class,
    ApiDocsProvider::class,
];
