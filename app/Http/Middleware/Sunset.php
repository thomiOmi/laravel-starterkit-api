<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class Sunset
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $date): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Sunset',
            (new DateTimeImmutable($date))->format(DateTimeInterface::RFC7231),
        );

        return $response;
    }
}
