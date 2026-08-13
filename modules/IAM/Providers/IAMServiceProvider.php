<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Http\Middleware\EnsureUserIsActive;

/**
 * Wires the IAM module into the framework.
 *
 * Declaration-only provider: the base class handles config merge, build-time
 * features, migrations, routes, and translations. This provider only declares
 * the module name and registers IAM-specific middleware aliases.
 */
class IAMServiceProvider extends ModuleServiceProvider
{
    /**
     * The TitleCase module folder name.
     */
    protected function moduleName(): string
    {
        return 'IAM';
    }

    /**
     * Register IAM-specific middleware aliases used by the module routes.
     */
    protected function bootModule(): void
    {
        Route::aliasMiddleware('active', EnsureUserIsActive::class);
    }
}
