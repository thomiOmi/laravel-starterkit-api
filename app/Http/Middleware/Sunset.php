<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ProblemResponse;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add RFC 8594 Sunset and RFC 9745 Deprecation headers to an endpoint.
 *
 * Usage via route middleware alias `sunset`:
 *
 * ```
 * // Only headers — inform client about future deprecation
 * Route::get(...)->middleware('sunset:2027-01-01');
 *
 * // + successor-version Link header
 * Route::get(...)->middleware('sunset:2027-01-01,https://v2.example.com/resource');
 *
 * // + enforce 410 Gone (blocks request after sunset date)
 * Route::get(...)->middleware('sunset:2027-01-01,enforce');
 *
 * // All three: date + successor URL + enforce
 * Route::get(...)->middleware('sunset:2027-01-01,https://v2.example.com/resource,enforce');
 * ```
 *
 * Middleware parameters are **order-independent** — URL is detected by `http` prefix
 * and `enforce` is detected by exact string match.
 */
final readonly class Sunset
{
    /**
     * @param  Closure(Request): (Response)  $next
     * @param  array<int, string>  $params  Variadic params: date as first, then URL and/or 'enforce' in any order
     */
    public function handle(Request $request, Closure $next, string $sunsetAt, string ...$params): Response
    {
        $sunsetDate = CarbonImmutable::parse($sunsetAt);

        $successorUrl = $this->resolveSuccessorUrl($params);
        $enforce = $this->resolveEnforce($params);

        if ($enforce && now()->greaterThanOrEqualTo($sunsetDate)) {
            $response = new ProblemResponse(
                typeKey: 'gone',
                title: __('auth.forbidden'),
                status: Response::HTTP_GONE,
                detail: __('general.sunset_endpoint_unavailable'),
            );

            return $this->attachHeaders($response->toResponse($request), $sunsetDate, $successorUrl);
        }

        return $this->attachHeaders($next($request), $sunsetDate, $successorUrl);
    }

    /**
     * @param  array<int, string>  $params
     */
    private function resolveSuccessorUrl(array $params): ?string
    {
        foreach ($params as $param) {
            if (str_starts_with($param, 'http')) {
                return $param;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $params
     */
    private function resolveEnforce(array $params): bool
    {
        return in_array('enforce', $params, true);
    }

    private function attachHeaders(Response $response, CarbonImmutable $sunsetDate, ?string $successorUrl): Response
    {
        $response->headers->set('Deprecation', '@'.$sunsetDate->timestamp);
        $response->headers->set('Sunset', $sunsetDate->format('D, d M Y H:i:s').' GMT');

        if ($successorUrl && filter_var($successorUrl, FILTER_VALIDATE_URL)) {
            $linkValue = sprintf('<%s>; rel="successor-version"', $successorUrl);
            $existingLink = $response->headers->get('Link');
            $response->headers->set('Link', $existingLink ? $existingLink.', '.$linkValue : $linkValue);
        }

        return $response;
    }
}
