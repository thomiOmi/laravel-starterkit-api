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

        $this->mergeConfigFrom(
            base_path("modules/{$this->name}/config/permission.php"),
            'permission',
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $configPath = base_path("modules/{$this->name}/config/config.php");
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->nameLower);
        }

        $this->commands($this->commands);

        if (app()->has('modules') && resolve('modules')->find($this->name) !== null) {
            parent::boot();
        } else {
            $this->loadMigrationsFrom(base_path("modules/{$this->name}/database/migrations"));
        }

        Route::aliasMiddleware('active', EnsureUserIsActive::class);
    }
}
