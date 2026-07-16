<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ProblemResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated user has a verified email address.
 *
 * Usage: `Route::get(...)->middleware('auth:sanctum', 'verified')`
 * Returns RFC 9457 ProblemResponse with 403 when the email is unverified.
 */
final readonly class EnsureEmailIsVerified
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return new ProblemResponse(
                typeKey: 'unauthenticated',
                title: __('auth.http_unauthorized'),
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            )->toResponse($request);
        }

        if (! $user->hasVerifiedEmail()) {
            return new ProblemResponse(
                typeKey: 'forbidden',
                title: __('auth.email_not_verified'),
                status: Response::HTTP_FORBIDDEN,
                detail: __('auth.email_verify_required'),
            )->toResponse($request);
        }

        return $next($request);
    }
}
