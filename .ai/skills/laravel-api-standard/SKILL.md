---
name: laravel-api-standard
description: Implement industrial-grade API standards including RFC 9457 (Problem Details), Idempotency, Stream Responses, and Rate Limit headers. Use when designing API endpoints, handling errors, or optimizing large data exports.
license: MIT
metadata:
  version: "1.0.0"
---

# Laravel Industrial API Standards

Guidelines and templates for building professional, stable, and transparent APIs for SPA and Mobile consumers.

## 1. Error Handling (RFC 9457)
Always return a `ProblemResponse` for errors. It ensures the client receives a structured, predictable error object.

### Code Template: ProblemResponse
```php
use App\Http\Responses\ProblemResponse;

// Inside a Controller or Exception Handler
return new ProblemResponse(
    type: 'https://api.example.com/errors/insufficient-permissions',
    title: 'Forbidden',
    status: 403,
    detail: 'You do not have the required role to access this resource.',
    instance: $request->path(),
    additional: [
        'required_permission' => 'posts.create'
    ]
);
```

## 2. Idempotency (Idempotency-Key)
Prevent duplicate data creation during network instability.

### Code Template: Idempotency Logic
```php
final readonly class StoreOrderController extends Controller
{
    public function __invoke(StoreOrderRequest $request, StoreOrderAction $action): SuccessResponse|ProblemResponse
    {
        $key = $request->header('Idempotency-Key');

        if ($key && $cached = Idempotency::get($key)) {
            return $cached;
        }

        $payload = $request->toPayload();
        $order = $action->handle($payload);

        $response = new SuccessResponse(new OrderResource($order));

        if ($key) {
            Idempotency::set($key, $response, ttl: now()->addDay());
        }

        return $response;
    }
}
```

## 3. Streaming Responses
Efficiently handle large data exports without exhausting server memory.

### Code Template: CSV Streaming
```php
use Symfony\Component\HttpFoundation\StreamedResponse;

return new StreamedResponse(function () use ($query) {
    $handle = fopen('php://output', 'w');
    fputcsv($handle, ['ID', 'Email', 'Created At']);

    $query->chunk(500, function ($users) use ($handle) {
        foreach ($users as $user) {
            fputcsv($handle, [$user->id, $user->email, $user->created_at]);
        }
    });

    fclose($handle);
}, 200, [
    'Content-Type' => 'text/csv',
    'Content-Disposition' => 'attachment; filename="users-export.csv"',
]);
```

## 4. Header Standards
- **Rate Limiting:** Ensure the `api` middleware is active to send `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `X-RateLimit-Reset`.
- **Media Access:** Use `Storage::temporaryUrl()` for private files.

## Constraints
- **MUST** use `SuccessResponse` and `ProblemResponse` wrappers.
- **MUST** use `JsonResource` for all data transformation.
- **MUST** support `Idempotency-Key` for critical state-changing endpoints.
