<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Sanctum\PersonalAccessToken;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->configureDefaults();

        $this->configureRateLimiting();

        $this->defineFeatures();
        Gate::before(function ($user, $ability) {
            /** @var User $user */
            return $user->hasRole('super-admin') ? true : null;
        });

        $this->configureEmailVerification();
        $this->configurePasswordReset();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::shouldBeStrict(! app()->isProduction());

        FormRequest::failOnUnknownFields(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
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
            /** @var int $limit */
            $limit = config('rate-limiting.api.limit');

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            /** @var int $perEmail */
            $perEmail = config('rate-limiting.auth.limit_per_email');
            /** @var int $perIp */
            $perIp = config('rate-limiting.auth.limit_per_ip');

            return [
                Limit::perMinute($perEmail)
                    ->by($request->input('email')),
                Limit::perMinute($perIp)
                    ->by($request->ip()),
            ];
        });

        RateLimiter::for('authenticated', function (Request $request) {
            /** @var int $limit */
            $limit = config('rate-limiting.authenticated.limit');

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function configurePasswordReset(): void
    {
        ResetPassword::createUrlUsing(function (mixed $user, string $token): string {
            /** @var User $user */
            /** @var string $frontendUrl */
            $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

            return $frontendUrl.'/reset-password?token='.$token.'&email='.$user->getEmailForPasswordReset();
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
