<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class PasswordExpiredMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        /** @var int $passwordExpiryDays */
        $passwordExpiryDays = config('auth.password_expiry_days', 90);

        /** @var Carbon|null $passwordChangedAt */
        $passwordChangedAt = $user->password_changed_at;

        if ($passwordChangedAt && $passwordChangedAt->copy()->addDays($passwordExpiryDays)->isPast()) {
            // Whitelist routes
            if ($request->is('api/v1/auth/*') || $request->is('api/v1/health')) {
                return $next($request);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Your password has expired. Please update your password.',
                'code' => 'PASSWORD_EXPIRED',
            ], 403);
        }

        return $next($request);
    }
}
