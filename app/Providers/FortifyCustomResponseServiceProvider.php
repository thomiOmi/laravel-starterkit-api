<?php

declare(strict_types=1);

namespace App\Providers;

use App\Traits\ApiResponser;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Contracts\PasswordUpdateResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class FortifyCustomResponseServiceProvider extends ServiceProvider
{
    use ApiResponser;

    public function register(): void
    {
        $this->app->singleton(TwoFactorAuthenticationProviderContract::class, TwoFactorAuthenticationProvider::class);

        // Register Response
        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse
            {
                use ApiResponser;

                public function toResponse($request)
                {
                    return $this->successResponse(null, __('auth.registered'), 201);
                }
            };
        });

        // Login Response
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse
            {
                use ApiResponser;

                public function toResponse($request)
                {
                    $user = $request->user();
                    $token = $user->createToken('auth_token')->plainTextToken;

                    return $this->successResponse([
                        'user' => $user,
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ], __('auth.login_success'));
                }
            };
        });

        // Logout Response
        $this->app->singleton(LogoutResponse::class, function () {
            return new class implements LogoutResponse
            {
                use ApiResponser;

                public function toResponse($request)
                {
                    return $this->successResponse(null, __('auth.logout_success'));
                }
            };
        });

        // Password Reset Link Response
        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponse::class, function () {
            return new class implements SuccessfulPasswordResetLinkRequestResponse
            {
                use ApiResponser;

                public function toResponse($request)
                {
                    return $this->successResponse(null, __('auth.password_reset_link_sent'));
                }
            };
        });

        // Password Reset Response
        $this->app->singleton(PasswordResetResponse::class, function () {
            return new class implements PasswordResetResponse
            {
                use ApiResponser;

                public function toResponse($request)
                {
                    return $this->successResponse(null, __('auth.password_reset_success'));
                }
            };
        });

        // Password Update Response
        $this->app->singleton(PasswordUpdateResponse::class, function () {
            return new class implements PasswordUpdateResponse
            {
                use ApiResponser;

                public function toResponse($request)
                {
                    return $this->successResponse(null, __('auth.password_updated'));
                }
            };
        });

        // Two Factor Login Response
        $this->app->singleton(TwoFactorLoginResponse::class, function () {
            return new class implements TwoFactorLoginResponse
            {
                use ApiResponser;

                public function toResponse($request)
                {
                    $user = $request->user();
                    $token = $user->createToken('auth_token')->plainTextToken;

                    return $this->successResponse([
                        'user' => $user,
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ], __('auth.login_success'));
                }
            };
        });
    }
}
