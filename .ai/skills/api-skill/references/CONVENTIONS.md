# Conventions Reference

This document covers folder structure, naming conventions, and complete worked examples for the API skill.

---

## 1. Folder Structure

This project follows a strict **Domain-Driven Modular Architecture**. All domain logic must reside within versioned modules.

```text
modules/
  {Module}/
    Actions/            # Business logic classes
    Controllers/
      V1/               # Versioned single-action controllers
    Payloads/
      V1/               # Versioned Data Transfer Objects
    Models/             # Eloquent models
    Requests/
      V1/               # Versioned form requests
    Resources/          # Eloquent resources
    Routes/
      v1.php            # Version-specific route definitions
    Filters/            # Query filters (extending BaseFilter)
    Database/           # Migrations, Factories, Seeders
    Tests/
      Feature/
        V1/             # Versioned feature tests
```

---

## 2. Naming Conventions

| Layer | Convention | Example |
|---|---|---|
| **Controller** | `V{Version}\{Action}Controller` | `V1\StoreController` |
| **Action** | `{Action}{Resource}Action` | `StoreUserAction` |
| **Payload** | `V{Version}\{Action}{Resource}Payload` | `V1\StoreUserPayload` |
| **Form Request** | `V{Version}\{Action}{Resource}Request` | `V1\StoreUserRequest` |
| **API Resource** | `{Resource}Resource` | `UserResource` |
| **Job** | `{Action}{Resource}Job` | `DeletePostJob` |
| **Filter** | `{Resource}Filter` | `UserFilter` |
| **Policy** | `{Resource}Policy` | `PostPolicy` |
| **Route Name** | `{module_name}.{action}` | `users.store` |
| **Test File** | `V{Version}\{Action}Test.php` | `V1\StoreTest.php` |

---

## 3. Implementation — Error Handling (RFC 9457)

### ProblemResponse Class
```php
final readonly class ProblemResponse implements Responsable
{
    public function __construct(
        private string $type,
        private string $title,
        private int    $status,
        private string $detail,
        private array  $errors = [],
    ) {}

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(
            data: array_filter([
                'type'   => $this->type,
                'title'  => $this->title,
                'status' => $this->status,
                'detail' => $this->detail,
                'errors' => $this->errors ?: null,
            ]),
            status:  $this->status,
            headers: ['Content-Type' => 'application/problem+json'],
        );
    }
}
```

### Exception Handler (`bootstrap/app.php`)
```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (ValidationException $e, Request $request): ProblemResponse {
        return new ProblemResponse(
            type:   'https://example.com/problems/validation-error',
            title:  'Validation Error',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            detail: 'The given data was invalid.',
            errors: $e->errors(),
        );
    });

    $exceptions->render(function (AuthenticationException $e, Request $request): ProblemResponse {
        return new ProblemResponse(
            type:   'https://example.com/problems/unauthenticated',
            title:  'Unauthenticated',
            status: Response::HTTP_UNAUTHORIZED,
            detail: 'You are not authenticated.',
        );
    });

    $exceptions->render(function (AuthorizationException $e, Request $request): ProblemResponse {
        return new ProblemResponse(
            type:   'https://example.com/problems/forbidden',
            title:  'Forbidden',
            status: Response::HTTP_FORBIDDEN,
            detail: 'You are not authorised to perform this action.',
        );
    });

    $exceptions->render(function (ModelNotFoundException $e, Request $request): ProblemResponse {
        return new ProblemResponse(
            type:   'https://example.com/problems/not-found',
            title:  'Not Found',
            status: Response::HTTP_NOT_FOUND,
            detail: 'The requested resource could not be found.',
        );
    });

    $exceptions->render(function (\Throwable $e, Request $request): ProblemResponse {
        return new ProblemResponse(
            type:   'https://example.com/problems/server-error',
            title:  'Server Error',
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
            detail: 'An unexpected error occurred.',
        );
    });
})
```

---

## 4. Implementation — Custom Middleware

### ForceJsonResponse
Sets `Accept: application/json` on all requests.
```php
public function handle(Request $request, Closure $next): Response
{
    $request->headers->set('Accept', 'application/json');
    return $next($request);
}
```

### Sunset (RFC 8594)
Attaches a `Sunset` header to deprecated routes.
```php
public function handle(Request $request, Closure $next, string $date): Response
{
    $response = $next($request);
    $response->headers->set(
        'Sunset',
        (new DateTimeImmutable($date))->format(DateTimeInterface::RFC7231),
    );
    return $response;
}
```

---

## 5. Implementation — Authorization (Policies)

Perform instance-level checks in the Form Request's `authorize()` method.

### The Policy (`modules/Post/Policies/PostPolicy.php`)
```php
final class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
```

### The Form Request
```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('post'));
}
```

---

## 6. Worked Example — Registration (Synchronous)

Registration must be synchronous as the user needs a token immediately.

```php
// Action
public function execute(V1\RegisterPayload $payload): array
{
    return $this->database->transaction(function () use ($payload): array {
        $user = User::query()->create($payload->toArray());
        $token = $user->createToken(name: 'api')->plainTextToken;

        return compact('user', 'token');
    });
}
```

---

## 7. Anti-Patterns Table

| Anti-Pattern | Correct Approach |
|---|---|
| Auto-increment IDs on public resources | Flexible: Use UUID/ULID if enumeration is a risk. |
| Business logic in Models or Controllers | Move to **Action Classes**. |
| Resourceful or multi-method controllers | One **`final` invokable controller** per operation. |
| Returning raw Models or Arrays | Always return an **API Resource**. |
| `app()` or `resolve()` inside methods | **Constructor Injection** always. |
| `DB::transaction()` Facade | Inject **`DatabaseManager`**. |
| `paginate()` on list endpoints | **`simplePaginate()`** — no `COUNT(*)` overhead. |
| Unthrottled routes | Always include **`throttle:api`**. |
| HTML error responses | **`ForceJsonResponse`** + RFC 9457 handler. |
| Policy/Gate checks inside an Action | Authorize in **`FormRequest::authorize()`** only. |
| `if/elseif` for selecting values | Use **`match`** expressions. |
| Omit `declare(strict_types=1)` | First statement in **every** file. |
