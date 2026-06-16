<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->defineFeatures();

        Model::shouldBeStrict(! app()->isProduction());

        $this->configureRateLimiting();

        Gate::before(function ($user, $ability) {
            /** @var User $user */
            return $user->hasRole('super-admin') ? true : null;
        });

        $this->configureEmailVerification();
    }

    protected function defineFeatures(): void
    {
        Feature::define('beta-feature', function (User $user) {
            return $user->hasRole('admin');
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = 60;
            $by = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($limit)->by($by);
        });

        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->input('email')),
                Limit::perMinute(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('authenticated', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function configureEmailVerification(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            /** @var User $notifiable */
            $expireConfig = config('auth.verification.expire', 60);
            $expire = is_numeric($expireConfig) ? (int) $expireConfig : 60;

            $signedRouteUrl = URL::temporarySignedRoute(
                'api.v1.auth.verification.verify',
                now()->addMinutes($expire),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );

            $query = parse_url($signedRouteUrl, PHP_URL_QUERY);

            $frontendUrlConfig = config('app.frontend_url');
            $frontendUrl = is_string($frontendUrlConfig) ? $frontendUrlConfig : 'http://localhost:5173';

            return $query !== null && $query !== false
                ? $frontendUrl.'/verify-email?'.$query
                : $frontendUrl.'/verify-email';
        });
    }
}
