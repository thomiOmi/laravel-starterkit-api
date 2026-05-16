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
            title: 'Validation Error',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            detail: 'The given data was invalid.',
            type: 'https://example.com/problems/validation-error',
            errors: $e->errors(),
            instance: $request->path(),
        );
    });

    $exceptions->render(function (AuthenticationException $e, Request $request): ProblemResponse {
        return new ProblemResponse(
            title: 'Unauthenticated',
            status: Response::HTTP_UNAUTHORIZED,
            detail: 'You must be authenticated to access this resource.',
            type: 'https://example.com/problems/unauthenticated',
        );
    });

    $exceptions->render(function (AuthorizationException $e, Request $request): ProblemResponse {
        return new ProblemResponse(
            title: 'Forbidden',
            status: Response::HTTP_FORBIDDEN,
            detail: 'You are not authorised to perform this action.',
            type: 'https://example.com/problems/forbidden',
        );
    });
})
```

---

## 3b. Implementation — Domain Errors from Actions

When an Action needs to signal a business rule violation, throw a dedicated domain exception and handle it in `bootstrap/app.php`.

### Step 1: Create the exception
```php
// modules/Payment/Exceptions/InsufficientBalanceException.php
final class InsufficientBalanceException extends RuntimeException {}
```

### Step 2: Throw from Action
```php
final readonly class ProcessPaymentAction
{
    public function handle(ProcessPaymentPayload $payload): Payment
    {
        if ($payload->amount > $this->getBalance($payload->accountId)) {
            throw new InsufficientBalanceException('Account balance is insufficient.');
        }
        // ...
    }
}
```

### Step 3: Handle in bootstrap/app.php
```php
$exceptions->render(function (InsufficientBalanceException $e, Request $request): ProblemResponse {
    return new ProblemResponse(
        title:  'Insufficient Balance',
        status: Response::HTTP_UNPROCESSABLE_ENTITY,
        detail: $e->getMessage(),
        type:   'https://example.com/problems/insufficient-balance',
    );
});
```

---

## 4. Implementation — Success Responses (`JsonDataResponse`)

### Response Shape
```json
{
    "data": { ... },
    "message": "User created successfully"
}
```

### Usage in Controller
```php
use App\Http\Responses\JsonDataResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class StoreController
{
    public function __construct(
        private StoreUserAction $storeUser,
    ) {}

    public function __invoke(StoreUserRequest $request): JsonDataResponse
    {
        $user = $this->storeUser->handle($request->payload());

        return new JsonDataResponse(
            data:    new UserResource($user),
            status:  Response::HTTP_CREATED,
            message: 'User created successfully',
        );
    }
}
```

### For Accepted (Async) Responses
```php
return new JsonDataResponse(
    data:    null,
    status:  Response::HTTP_ACCEPTED,
    message: 'Request accepted for processing',
);
```

### For Empty Delete Responses
```php
return new JsonDataResponse(
    data:    null,
    status:  Response::HTTP_NO_CONTENT,
    message: 'Resource deleted',
);
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

## 6. Implementation — Testing (Pest PHP)

> **Response shape reference**:
> - Success responses: `{ "data": {...}, "message": "..." }`
> - Problem responses: `{ "type": "...", "title": "...", "status": N, "detail": "...", "errors"?: {...} }`

### Outside-In Feature Test (`modules/User/Tests/Feature/V1/StoreTest.php`)
```php
uses(RefreshDatabase::class);

it('can store a user', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('data.name', 'John Doe');
});
```

---

## External References

- **Laravel 13**: [https://laravel.com/docs/13.x](https://laravel.com/docs/13.x)
- **RFC 9457 (Problem Details)**: [https://www.rfc-editor.org/rfc/rfc9457](https://www.rfc-editor.org/rfc/rfc9457)
- **RFC 8594 (Sunset Header)**: [https://www.rfc-editor.org/rfc/rfc8594](https://www.rfc-editor.org/rfc/rfc8594)
- **Spatie Permission**: [https://spatie.be/docs/laravel-permission](https://spatie.be/docs/laravel-permission)
