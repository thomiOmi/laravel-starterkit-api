# Architecture Reference

This project follows a **Domain-Driven Modular Architecture**. It separates the application into self-contained modules, each representing a specific domain.

---

## 1. Module Layout

Every module in `modules/` must follow this blueprint:

```text
modules/
  {Module}/
    Actions/            # Atomic or Orchestrator actions (Business Logic)
    Controllers/
      V1/               # Single-action controllers
    Payloads/
      V1/               # Type-safe data objects
    Requests/
      V1/               # Validation and Authorization (Policies)
    Resources/          # Eloquent Resources
    Models/             # Eloquent Models
    Filters/            # BaseFilter implementations
    Routes/
      V1.php            # Route definitions (Uppercase V1)
    Database/
      Migrations/
      Factories/        # Mandatory for testing
      Seeders/
    Events/             # Cross-module communication (Side-effects)
    Listeners/
    Tests/
      Feature/
        V1/             # Feature tests for version 1
```

## 2. Communication Rules

### Asynchronous / Side-Effects
Use **Events** and **Listeners** for cross-module side effects. For example, when an `Order` is created in the `Order` module, a listener in the `Inventory` module should decrement stock.

### Synchronous / Data Retrieval
Modules are permitted to read **Models** from other modules directly for data retrieval. This avoids over-engineering with complex service layers or internal repositories.

## 3. The Orchestrator Pattern

For complex operations (e.g., Checkout), create a main Action that coordinates multiple atomic actions within its module or triggers events for other modules.

```php
// Orchestrator Action example
final readonly class CheckoutAction
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private ProcessPaymentAction $processPayment,
        private DatabaseManager $database,
    ) {}

    public function handle(CheckoutPayload $payload): Order
    {
        return $this->database->transaction(function() use ($payload) {
            $order = $this->createOrder->handle($payload->toOrderPayload());
            $this->processPayment->handle($order, $payload->toPaymentPayload());

            event(new OrderPlaced($order));

            return $order;
        });
    }
}
```
