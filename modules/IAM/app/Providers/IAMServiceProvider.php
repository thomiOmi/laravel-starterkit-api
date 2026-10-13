<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Support\Facades\Route;
use Modules\IAM\Http\Middleware\EnsureUserIsActive;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Wires the IAM module into the framework.
 *
 * The nWidart base class handles module config merge, migrations, views,
 * and translations. This provider declares the module name, merges the
 * IAM-owned Spatie permission config under the `permission` key, registers
 * the module route provider, and registers IAM-specific middleware aliases.
 */
class IAMServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'IAM';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'iam';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        $module = $this->app->bound('modules') ? $this->app->make('modules')->find($this->name) : null;
        $configPath = $module !== null
            ? module_path($this->name, 'config/permission.php')
            : dirname(__DIR__, 2).'/config/permission.php';

        $this->mergeConfigFrom($configPath, 'permission');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $module = $this->app->bound('modules') ? resolve('modules')->find($this->name) : null;

        if ($module !== null) {
            parent::boot();
        } else {
            $migrationPath = dirname(__DIR__, 2).'/database/migrations';
            if (is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }

        Route::aliasMiddleware('active', EnsureUserIsActive::class);
    }
}
