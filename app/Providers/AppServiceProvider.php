<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Identity;
use App\Enums\RoleEnum;
use App\Models\Sanctum\PersonalAccessToken;
use App\Support\Production\ProductionSecurityCheck;
use Carbon\CarbonImmutable;
use Composer\InstalledVersions;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Uri;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;
use RuntimeException;

/**
 * Central application configuration hub.
 *
 * Registers rate limiters, auth defaults (email verification, password reset),
 * the SuperAdmin gate, production security monitoring, and immutable date
 * handling.
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

        $this->configureDevCommands();

        $this->configureDefaults();

        $this->configureRateLimiting();

        $this->configureSuperAdminGate();

        $this->configureEmailVerificationUrl();

        $this->configureEmailVerificationMail();

        $this->configurePasswordResetMail();

        $this->monitorProductionSecurity();
    }

    /**
     * Customize the default dev processes (server, queue, logs) via DevCommands.
     *
     * @see https://laravel.com/docs/13.x/artisan#customizing-dev-processes
     * @see https://github.com/laravel/framework/blob/13.x/src/Illuminate/Foundation/DevCommands.php
     *
     * Uses the host/port from `app.url` for the server. On Windows the `logs`
     * process (Pail) is automatically excluded because pcntl_fork is unavailable.
     */
    protected function configureDevCommands(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        DevCommands::except('vite');

        $url = config()->string('app.url', 'http://localhost');

        DevCommands::artisan(sprintf(
            'serve --host=%s --port=%s',
            parse_url($url, PHP_URL_HOST) ?? 'localhost',
            parse_url($url, PHP_URL_PORT) ?? '8000'
        ), 'server');
        DevCommands::artisan('queue:listen --tries=1 --timeout=0', 'queue');

        if (function_exists('pcntl_fork') && InstalledVersions::isInstalled('laravel/pail')) {
            DevCommands::artisan('pail --timeout=0', 'logs');
        }
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

    /**
     * Define API rate limiters for the api, auth, and authenticated routes.
     */
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

    /**
     * Grant SuperAdmin unrestricted access via Gate::before.
     *
     * Returning null allows other policies to decide when the user
     * does not have the SuperAdmin role.
     */
    protected function configureSuperAdminGate(): void
    {
        Gate::before(function (Identity $user, string $ability) {
            return $user->hasRole(RoleEnum::SuperAdmin->value) ? true : null;
        });
    }

    /**
     * The configured frontend base URI (scheme + host + port).
     */
    protected function frontendUri(): Uri
    {
        return Uri::of(config()->string('app.frontend_url', 'http://localhost:5173'));
    }

    /**
     * Customize the signed email verification URL for the SPA frontend.
     *
     * The signed URL is generated with a configurable expiration, then
     * merged with the user id/hash and forwarded to the frontend app.
     */
    protected function configureEmailVerificationUrl(): void
    {
        VerifyEmail::createUrlUsing(function (Identity $notifiable): string {
            $expire = config()->integer('auth.verification.expire', 60);

            $params = [
                'id' => $notifiable->getAuthIdentifier(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ];

            $signedUri = Uri::temporarySignedRoute(
                'api.v1.iam.auth.verification.verify',
                now()->addMinutes($expire),
                $params,
            );

            $frontend = $this->frontendUri();
            $scheme = $frontend->scheme();
            $host = $frontend->host();

            if ($scheme === null || $host === null) {
                throw new RuntimeException(
                    'The "app.frontend_url" config value must be an absolute URL with a scheme and host.'
                );
            }

            return (string) $signedUri
                ->withScheme($scheme)
                ->withHost($host)
                ->withPort($frontend->port())
                ->withPath('/verify-email')
                ->withQuery($params);
        });
    }

    /**
     * Customize the email verification notification message.
     */
    protected function configureEmailVerificationMail(): void
    {
        VerifyEmail::toMailUsing(function (mixed $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject(__('auth.email_verify_subject'))
                ->line(__('auth.email_verify_line'))
                ->action(__('auth.email_verify_action'), $url)
                ->line(__('auth.email_verify_footer'));
        });
    }

    /**
     * Customize the password reset notification message.
     */
    protected function configurePasswordResetMail(): void
    {
        ResetPassword::toMailUsing(function (mixed $notifiable, string $token): MailMessage {
            $url = $this->frontendPasswordResetUrl($token, $notifiable);

            $expire = config()->integer(
                'auth.passwords.'.config()->string('auth.defaults.passwords').'.expire',
                60,
            );

            return (new MailMessage)
                ->subject(__('auth.password_reset_subject'))
                ->line(__('auth.password_reset_line'))
                ->action(__('auth.password_reset_action'), $url)
                ->line(__('auth.password_reset_expire', ['count' => $expire]))
                ->line(__('auth.password_reset_footer'));
        });
    }

    /**
     * Build the frontend password reset URL with the token and an encoded email.
     */
    protected function frontendPasswordResetUrl(string $token, mixed $user): string
    {
        $params = ['token' => $token];

        if ($user instanceof Identity) {
            $params['email'] = $user->getEmailForPasswordReset();
        }

        return (string) $this->frontendUri()
            ->withPath('/reset-password')
            ->withQuery($params);
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
