<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Automatically discovers and registers module service providers.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
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
