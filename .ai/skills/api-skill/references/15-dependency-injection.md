# Dependency Injection Standards

We strictly use **Constructor Injection** to manage dependencies. This ensures that our classes are decoupled, highly testable, and their dependencies are explicit.

## 1. Core Principles

- **Constructor Injection**: Always declare dependencies in the class constructor.
- **Explicit Dependencies**: If a class needs another service, action, or repository, it must be injected, not resolved manually.
- **No Manual Resolution**: Avoid using `app()`, `resolve()`, or `make()` inside methods.
- **No Facades in Logic**: Avoid using Facades (e.g., `DB`, `Gate`, `Auth`) inside Controllers or Actions where a dependency can be injected instead.

## 2. Implementation Example

### Correct Approach (Constructor Injection):
```php
final class StoreController extends Controller
{
    public function __construct(
        private readonly StoreUserAction $action,
        private readonly DatabaseManager $database
    ) {}

    public function __invoke(UserRequest $request): JsonResponse
    {
        // $this->action and $this->database are readily available
    }
}
```

### Wrong Approach (Manual Resolution):
```php
public function __invoke(UserRequest $request): JsonResponse
{
    // ❌ Never do this
    $action = app(StoreUserAction::class);

    // ❌ Avoid Facades when injection is possible
    DB::transaction(fn() => ...);
}
```

## 3. Why use Dependency Injection?

1. **Testability**: You can easily swap real implementations with mocks during testing.
2. **Clarity**: You can see exactly what a class needs just by looking at its constructor.
3. **Container Power**: Laravel's service container automatically resolves and injects these dependencies for you.

## 4. Anti-Patterns

- ❌ Do not use the `app()` helper inside any method.
- ❌ Do not use Facades in business logic (Actions/Controllers) if a concrete class or interface can be injected.
- ❌ Do not create "Hidden Dependencies" by instantiating classes directly (`new Service()`).
