# Custom Middleware Standards

We use custom middleware to enforce API-specific requirements like JSON headers and version deprecation.

## 1. ForceJsonResponse

Ensures the API always returns JSON by setting the `Accept: application/json` header on all inbound requests. This ensures that even low-level middleware failures return JSON instead of HTML.

- **Location**: `app/Http/Middleware/ForceJsonResponse.php`
- **Application**: Must be the **first** middleware in every API route group stack.

```php
public function handle(Request $request, Closure $next): Response
{
    $request->headers->set('Accept', 'application/json');
    return $next($request);
}
```

## 2. Sunset (RFC 8594)

Signals that an endpoint is deprecated and scheduled for removal by attaching a `Sunset` header.

- **Location**: `app/Http/Middleware/Sunset.php`
- **Application**: Applied to deprecated route groups with a specific date (e.g., `sunset:2026-12-31`).

```php
// Middleware handles formatting the date to RFC 7231
$response->headers->set(
    'Sunset',
    (new DateTimeImmutable($date))->format(DateTimeInterface::RFC7231),
);
```

## 3. Implementation Order

Standard API group middleware stack:
1. `force.json`
2. `auth:sanctum` (If protected)
3. `throttle:api`
4. `sunset:YYYY-MM-DD` (If deprecated)

## 4. Anti-Patterns

- ❌ Do not apply `auth:sanctum` before `force.json`.
- ❌ Do not omit `force.json` from API route groups.
- ❌ Do not return HTML error pages for API routes.
