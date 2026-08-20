<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Reject authenticated requests from users whose account status does not
 * allow authentication (inactive, suspended, or banned).
 *
 * @throws AuthenticationException When the request has no authenticated user.
 * @throws AccessDeniedHttpException When the user's status blocks authentication.
 */
final readonly class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException(__('auth.unauthenticated'));
        }

        if (! $user->status->allowsAuthentication()) {
            throw new AccessDeniedHttpException(__($user->status->blockedMessageKey()));
        }
        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
