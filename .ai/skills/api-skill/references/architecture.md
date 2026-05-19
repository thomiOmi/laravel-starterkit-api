# Architecture Reference (2026)

This project utilizes a **Domain-Driven Modular Architecture** with automated oversight via Architecture Testing.

---

## 1. Module Layout

Every module in `modules/` must follow this structure:

```text
modules/
  {Module}/
    Actions/            # Business Logic (Atomic/Orchestrator) - Final Readonly
    Controllers/
      V1/               # Single-action controllers - Final Readonly
    Payloads/
      V1/               # Data Objects - Final Readonly with Property Hooks
    Requests/
      V1/               # Validation & Authorization - Final
    Resources/          # Eloquent Resources - Final
    Models/             # Eloquent Models
    Filters/            # BaseFilter implementations - Final
    Routes/
      V1.php            # Route definitions
    Database/
      Migrations/
      Factories/
    Tests/
      Feature/
      Architecture/     # Module-specific Pest Arch rules
```

## 2. Communication Rules (The Rules of 2026)

### Synchronous (Read-only)
Modules are permitted to access **Models** from other modules for data retrieval purposes.

### Asynchronous (State-change)
Modules are **PROHIBITED** from calling Actions from other modules directly. Use **Events** and **Listeners** for cross-module side effects.

### Observability
All interactions between modules must carry the `trace_id` stored in Laravel **Context**.

## 3. The Orchestrator Pattern

For complex operations, use a primary Action (Orchestrator) that calls multiple atomic Actions within the same module.

```php
final readonly class CheckoutAction
{
    public function handle(CheckoutPayload $payload): Order
    {
        return $this->database->transaction(function() use ($payload) {
            $order = $this->createOrder->handle($payload->toOrderPayload());

            // Side effect via Event
            event(new OrderPlaced($order));

            // Background task via defer
            defer(fn() => $this->notifyAdmin($order));

            return $order;
        });
    }
}
```

## 4. Architecture Verification (Automated)

The integrity of this modular structure must be verified by **Pest Arch**. If a developer violates boundaries (e.g., a Controller accessing a Model directly), the test will fail automatically.
