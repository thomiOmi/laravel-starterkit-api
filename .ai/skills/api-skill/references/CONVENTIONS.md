# Conventions Reference

This document provides detailed folder structures, naming conventions, and implementation examples to support the API Skill.

---

## 1. Folder Structure (Detailed)

This project follows a strict **Domain-Driven Modular Architecture**.

```text
modules/
  {Module}/
    Actions/            # Business logic classes
    Controllers/
      V1/               # Single-action controllers (V1)
    Payloads/
      V1/               # Data Transfer Objects (V1)
    Models/             # Eloquent models (Domain root)
    Requests/
      V1/               # Form requests (V1)
    Resources/          # Eloquent resources
    Routes/
      v1.php            # Route definitions
    Filters/            # Query filters (BaseFilter)
    Policies/           # Laravel Policies
    Jobs/               # Background jobs
    Database/
      Migrations/
      Factories/
      Seeders/
    Tests/
      Feature/
        V1/             # Feature tests (V1)
```

---

## 2. Naming Conventions Table

| Layer | Convention | Example |
|---|---|---|
| **Controller** | `V{Version}\{Action}Controller` | `V1\StoreController` |
| **Action** | `{Action}{Resource}Action` | `StorePostAction` |
| **Payload** | `V{Version}\{Action}{Resource}Payload` | `V1\StoreUserPayload` |
| **Form Request** | `V{Version}\{Action}{Resource}Request` | `V1\StoreUserRequest` |
| **API Resource** | `{Resource}Resource` | `UserResource` |
| **Job** | `{Action}{Resource}Job` | `DeletePostJob` |
| **Filter** | `{Resource}Filter` | `UserFilter` |
| **Policy** | `{Resource}Policy` | `PostPolicy` |
| **Route Name** | `{module_name}.{action}` | `users.store` |

---

## 3. Implementation — Error Handling (RFC 9457)

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
})
```

---

## 4. Implementation — Success Responses (JSON Envelope)

### Standard Envelope Structure
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### Usage in Controller
```php
return $this->successResponse(
    data: new UserResource($user),
    message: 'User created successfully',
    status: Response::HTTP_CREATED
);
```

---

## 5. Implementation — Background Jobs

### The Job (`modules/Post/Jobs/DeletePostJob.php`)
```php
final class DeletePostJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private Post $post, // not readonly due to serialization
    ) {}

    public function handle(DatabaseManager $database): void
    {
        $database->transaction(fn () => $this->post->delete());
    }
}
```

### The Controller
```php
public function __invoke(Post $post): JsonResponse
{
    dispatch(new DeletePostJob($post));

    return $this->successResponse(
        data: null,
        message: 'Request accepted for processing',
        status: Response::HTTP_ACCEPTED
    );
}
```

---

## 6. Implementation — Testing (Pest PHP)

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

it('returns 422 if email is missing', function (): void {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/users', ['name' => 'John'])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error');
});
```

---

## 7. Implementation — Documentation (Scramble)

### Controller Annotations
```php
/**
 * @tags Post
 */
final class StoreController extends Controller
{
    #[BodyParameter(name: 'title', description: 'Post title', required: true)]
    public function __invoke(StoreRequest $request): JsonResponse { ... }
}
```

---

## 8. AppServiceProvider Setup

```php
public function boot(): void
{
    Model::shouldBeStrict(! app()->isProduction());

    RateLimiter::for('api', function (Request $request): Limit {
        return Limit::perMinute(60)->by(
            key: $request->user()?->id ?: $request->ip(),
        );
    });
}
```

---

## 9. Anti-Patterns Table

| Anti-Pattern | Correct Approach |
|---|---|
| Business logic in Models | Move to **Action Classes**. |
| Resourceful controllers | One **`final` invokable controller** per operation. |
| `app()` or `resolve()` | **Constructor Injection** always. |
| `paginate()` | **`simplePaginate()`** — no `COUNT(*)` overhead. |
| Unthrottled routes | Always include **`throttle:api`**. |
| HTML error responses | **`ForceJsonResponse`** middleware. |
| Policy checks in Action | Authorize in **`FormRequest::authorize()`** only. |
| `if/elseif` for values | Use **`match`** expressions. |
| `DataTableDTO` | Use **`BaseFilter`** + standard pagination. |
