<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\IAM\Http\Middleware\EnsureUserIsActive;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Modules\IAM\Policies\PermissionPolicy;
use Modules\IAM\Policies\RolePolicy;
use Modules\IAM\Policies\UserPolicy;

class IAMServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configurePolicies();

        $this->registerMiddlewareAliases();
    }

    public function register(): void {}

    /**
     * Register module policies explicitly.
     *
     * Laravel's policy auto-discovery only covers App\Models, so module
     * models require an explicit mapping.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
    }

    /**
     * Register middleware aliases used by the module routes.
     *
     * Route discovery is centralized in RouteServiceProvider.
     */
    protected function registerMiddlewareAliases(): void
    {
        Route::aliasMiddleware('active', EnsureUserIsActive::class);
    }
}
