<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulePath = base_path('modules');

        if (File::exists($modulePath)) {
            $modules = File::directories($modulePath);

            foreach ($modules as $module) {
                $moduleName = basename($module);
                $provider = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }
}
