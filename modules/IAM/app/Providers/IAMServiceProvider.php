<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Support\Facades\Route;
use Modules\IAM\Http\Middleware\EnsureUserIsActive;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Wires the IAM module into the framework.
 *
 * Declaration-only provider: the nWidart base class handles config merge,
 * migrations, views, and translations. This provider only declares the
 * module name, registers the module route provider, and registers
 * IAM-specific middleware aliases.
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
        RouteServiceProvider::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Route::aliasMiddleware('active', EnsureUserIsActive::class);
    }
}
