---
name: laravel-api-standard
description: Implement industrial-grade API standards including RFC 9457 (Problem Details), Idempotency, Stream Responses, and Rate Limit headers. Use when designing API endpoints, handling errors, or optimizing large data exports.
license: MIT
metadata:
  version: "1.1.0"
---

# Laravel Industrial API Standards

Guidelines and templates for building professional, stable, and transparent APIs for SPA and Mobile consumers.

## Gotchas
- **Don't leak stack traces:** Ensure `APP_DEBUG=false` in production so `ProblemResponse` doesn't expose sensitive info.
- **Idempotency Key Collision:** Use UUID v4 for `Idempotency-Key` to avoid collisions.
- **JSON Precision:** Always cast ID fields and formatted dates to `(string)` in Resources to avoid floating point or format issues in JS/Mobile.

## 1. Error Handling (RFC 9457)
Always return a `ProblemResponse` for errors. It ensures the client receives a structured, predictable error object.

### Template: ProblemResponse Implementation
```php
<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;

final readonly class ProblemResponse implements Responsable
{
    public function __construct(
        private string $type = 'about:blank',
        private string $title = 'An error occurred',
        private int $status = 500,
        private string $detail = '',
        private ?string $instance = null,
        private array $errors = [],
        private array $additional = []
    ) {}

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'type'     => $this->type,
            'title'    => $this->title,
            'status'   => $this->status,
            'detail'   => $this->detail,
            'instance' => $this->instance ?? $request->path(),
            'errors'   => $this->errors,
            ...$this->additional,
        ], $this->status);
    }
}
```

## 2. Idempotency (Idempotency-Key)
Prevent duplicate data creation during network instability.

### Procedure: Idempotency Check
1. Read `Idempotency-Key` from request headers.
2. Check if the key exists in Cache (Redis/Database).
3. If exists, return the cached response immediately.
4. If not, process the Action and cache the response for 24 hours.

### Template: Idempotent Controller
```php
final readonly class StoreTransactionController extends Controller
{
    public function __invoke(StoreRequest $request, StoreAction $action): SuccessResponse|ProblemResponse
    {
        $key = $request->header('Idempotency-Key');

        if ($key && $cached = Idempotency::get($key)) {
            return $cached;
        }

        $result = $action->handle($request->toPayload());
        $response = new SuccessResponse(new Resource($result));

        if ($key) {
            Idempotency::set($key, $response, ttl: 86400);
        }

        return $response;
    }
}
```

## 3. Streaming Responses
Efficiently handle large data exports without exhausting server memory.

### Template: CSV Streaming
```php
use Symfony\Component\HttpFoundation\StreamedResponse;

return new StreamedResponse(function () use ($query) {
    $handle = fopen('php://output', 'w');
    fputcsv($handle, ['ID', 'Email', 'Created At']);

    $query->chunk(1000, function ($records) use ($handle) {
        foreach ($records as $record) {
            fputcsv($handle, [$record->id, $record->email, $record->created_at->toDateTimeString()]);
        }
    });

    fclose($handle);
}, 200, [
    'Content-Type' => 'text/csv',
    'Content-Disposition' => 'attachment; filename="export.csv"',
]);
```

## Constraints
- **MUST** use `SuccessResponse` for 200/201 responses.
- **MUST** use `ProblemResponse` for 4xx/5xx responses.
- **MUST** read `references/api-standard-details.md` if the user asks for complex streaming logic.
