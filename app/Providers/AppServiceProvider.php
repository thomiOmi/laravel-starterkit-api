<?php

declare(strict_types=1);

namespace App\Providers;

use App\Agents\Antigravity;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Boost;
use Laravel\Sanctum\Sanctum;

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

        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Boost::registerAgent('antigravity', Antigravity::class);

        $this->configureRateLimiting();

        // Implicitly grant "super-admin" role all permissions
        // This works in the gate layer which is used by $user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
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

            if (tenancy()->initialized) {
                $limit = (int) tenant('rate_limit') ?: 60;
                $by = 'tenant:'.tenant('id').':'.$by;
            }

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
