<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;

/**
 * General application service provider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->defineFeatures();

        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        $this->configureRateLimiting();

        // Implicitly grant "super-admin" role all permissions
        // This works in the gate layer which is used by $user->can() and @can()
        Gate::before(function ($user, $ability) {
            /** @var User $user */
            return $user->hasRole('super-admin') ? true : null;
        });
    }

    /**
     * Define the feature flags for the application.
     */
    protected function defineFeatures(): void
    {
        Feature::define('beta-feature', function (User $user) {
            return $user->hasRole('admin');
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = 60;
            $by = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($limit)->by($by);
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('authenticated', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
