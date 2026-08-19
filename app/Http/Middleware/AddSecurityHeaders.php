<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attach security headers to every HTTP response.
 *
 * - HSTS is only sent in production environments.
 * - Remaining headers are set unconditionally.
 */
final readonly class AddSecurityHeaders
{
    private const array HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Frame-Options' => 'DENY',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'Cache-Control' => 'no-store',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return self::applyHeaders($next($request));
    }

    /**
     * Attach security headers to an already-built response.
     *
     * Also called from exception rendering, where middleware never runs.
     */
    public static function applyHeaders(Response $response): Response
    {
        foreach (self::HEADERS as $header => $value) {
            $response->headers->set($header, $value);
        }

        if (app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }
}
