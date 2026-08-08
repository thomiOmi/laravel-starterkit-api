<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException(__('auth.unauthenticated'));
        }

        // Identity extends MustVerifyEmail — the check is unconditional by contract.
        // A fork swapping the model without verification support fails loudly instead.
        if (! $user->hasVerifiedEmail()) {
            throw new AccessDeniedHttpException(__('auth.email_verify_required'));
        }
        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
