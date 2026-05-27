---
name: laravel-patterns
description: "Domain-Driven Modular Architecture for Laravel 13+, featuring Single-Action Controllers and Action Orchestration."
metadata:
  version: "1.1.0"
  triggers: "DDD, modules, Single-Action Controller, Action Pattern, Orchestrator, Database Transaction"
---

# Laravel Patterns (Standard 2026)

This skill guides the implementation of a scalable Domain-Driven Modular Architecture, ensuring business logic is decoupled and maintainable.

## 1. Directory Structure (Modules)
All domain logic MUST reside within `modules/{Module}/`. Use **Uppercase V1** for versioned directories.

```text
modules/{Module}/
├── Actions/            # Atomic business logic
├── Controllers/
│   └── V1/             # Invokable controllers
├── Models/             # Eloquent models
├── Payloads/
│   └── V1/             # Property Hook DTOs
├── Requests/
│   └── V1/             # Form Requests
├── Resources/          # API Resources
├── Routes/
│   └── V1.php          # Module-specific routes
└── Tests/
    ├── Feature/V1/
    └── Architecture/
```

## 2. Single-Action Controllers
Controllers MUST be `invokable` and focus on a single HTTP verb/route.
- **Dependency Injection**: Inject Actions via the constructor.
- **No Logic**: Controllers should only handle request-to-payload mapping and response returning.

```php
#[Group('Orders')]
final readonly class StoreOrderController
{
    public function __construct(
        private StoreOrderAction $action
    ) {}

    public function __invoke(StoreOrderRequest $request): JsonDataResponse
    {
        $order = $this->action->handle($request->toPayload());
        return new JsonDataResponse(new OrderResource($order));
    }
}
```

## 3. The Action Pattern
- **Atomic Actions**: One action per database operation.
- **Transactions**: Use `DatabaseManager $database` and `$this->database->transaction()` for all write operations.
- **Type Safety**: Use Payloads as input and Models/Resources as output.

## 4. Action Composition (Orchestrator)
For complex workflows, use a main Action to coordinate multiple sub-actions.
```php
final readonly class ProcessCheckoutAction
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private ProcessPaymentAction $processPayment,
        private SendNotificationAction $sendNotification
    ) {}

    public function handle(CheckoutPayload $payload): Order
    {
        // Coordination logic here
    }
}
```

## 5. Performance & Modern Laravel
- **Defer**: Use `defer()` for non-critical post-response tasks.
- **Concurrency**: Use `Concurrency::run()` for parallel I/O tasks.
- **Strict Models**: Always enable `Model::shouldBeStrict()`.

## 6. Anti-Patterns to Avoid
- ❌ No Repository Pattern.
- ❌ No multi-action controllers.
- ❌ No business logic in Models (use Scopes only).
- ❌ No direct Model usage in Controllers (must use Actions).
