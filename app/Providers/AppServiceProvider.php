<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Identity;
use App\Enums\RoleEnum;
use App\Models\Sanctum\PersonalAccessToken;
use App\Support\Production\ProductionSecurityCheck;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;

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

        Gate::before(function (Identity $user, string $ability) {
            return $user->hasRole(RoleEnum::SuperAdmin->value) ? true : null;
        });

        $this->configureEmailVerification();
        $this->configurePasswordReset();

        $this->monitorProductionSecurity();

        // $this->configureScramble();
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
        Feature::define('beta-feature', function (Identity $user) {
            return $user->hasRole(RoleEnum::Admin->value);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = config()->integer('rate-limiting.api.limit');

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            $perEmail = config()->integer('rate-limiting.auth.limit_per_email');
            $perIp = config()->integer('rate-limiting.auth.limit_per_ip');

            return [
                Limit::perMinute($perEmail)
                    ->by($request->input('email')),
                Limit::perMinute($perIp)
                    ->by($request->ip()),
            ];
        });

        RateLimiter::for('authenticated', function (Request $request) {
            $limit = config()->integer('rate-limiting.authenticated.limit');

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function configurePasswordReset(): void
    {
        ResetPassword::createUrlUsing(function (mixed $user, string $token): string {
            $frontendUrl = config()->string('app.frontend_url', 'http://localhost:5173');

            $url = $frontendUrl.'/reset-password?token='.$token;

            if ($user instanceof Identity) {
                $url .= '&email='.$user->getEmailForPasswordReset();
            }

            return $url;
        });
    }

    protected function configureEmailVerification(): void
    {
        VerifyEmail::createUrlUsing(function (Identity $notifiable): string {
            $expire = config()->integer('auth.verification.expire', 60);

            $signedRouteUrl = URL::temporarySignedRoute(
                'v1.auth.verification.verify',
                now()->addMinutes($expire),
                [
                    'id' => $notifiable->getAuthIdentifier(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );

            $params = [
                'id' => $notifiable->getAuthIdentifier(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ];

            $query = parse_url($signedRouteUrl, PHP_URL_QUERY);
            if ($query !== null && $query !== false) {
                parse_str($query, $existing);
                $params = array_merge($params, $existing);
            }

            $frontendUrl = config()->string('app.frontend_url', 'http://localhost:5173');

            return $frontendUrl.'/verify-email?'.http_build_query($params);
        });

        VerifyEmail::toMailUsing(function (mixed $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $url);
        });
    }

    /**
     * Log warnings for any production security misconfiguration.
     *
     * Does not abort — the app continues serving traffic. Use
     * the `security:check` Artisan command as a CI/CD gate.
     */
    protected function monitorProductionSecurity(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $check = new ProductionSecurityCheck;

        foreach ($check() as $result) {
            if ($result['status'] === 'fail') {
                Log::warning("Production security check failed: {$result['check']}", [
                    'check' => $result['check'],
                    'detail' => $result['detail'],
                ]);
            }
        }
    }
}
