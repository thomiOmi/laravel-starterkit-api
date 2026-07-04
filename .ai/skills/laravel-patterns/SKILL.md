---
name: laravel-patterns
description: Architectural patterns for Modular Monoliths. Handles Action-Payload patterns, Cross-module communication (Contracts/Events), and Service Layer orchestration.
license: MIT
metadata:
  version: "2.2.0"
---

# Laravel Architecture Patterns

Production-grade patterns for building clean, isolated, and scalable modular applications.

## 1. The Action-Payload Pattern
Decouple your business logic from the transport layer (HTTP, CLI, Jobs).

```mermaid
graph LR
    H[HTTP Request] --> P[Payload DTO]
    C[CLI Command] --> P
    J[Queue Job] --> P
    P --> A[Action Class]
    A --> O[Outcome]
```

### Implementation Checklist
- [ ] Create a `final readonly class Payload` with promoted properties.
- [ ] Create a `final readonly class Action` with a single `handle(Payload $p)` method.
- [ ] Use the Action in Controllers, Jobs, or Commands.

## 2. Cross-Module Communication
Maintain strict isolation between modules.

### Synchronous (Contracts)
If Module A needs a result from Module B immediately.
1. Define Interface in `app/Contracts/Modules/{Module}Contract.php`.
2. Implement in `Modules/{Module}/Services/{Module}Service.php`.
3. Bind in `Modules/{Module}/Providers/{Module}ServiceProvider.php`.

### Asynchronous (Event-Driven)
If Module A just needs to broadcast that something happened.
```mermaid
sequenceDiagram
    participant A as Module A
    participant E as Event: OrderPlaced
    participant B as Module B (Listener)

    A->>E: Dispatch Event
    E->>B: Execute Listener Logic
```

## 3. Service Layer Orchestration
Use Domain Services for complex logic involving multiple models or third-party integrations.

```php
final readonly class PaymentService
{
    public function __construct(
        private StripeGateway $gateway,
        private NotificationContract $notifier,
    ) {}

    public function process(PaymentPayload $payload): PaymentResult
    {
        // 1. Logic
        // 2. Integration
        // 3. Notification
    }
}
```

## Constraints
- **MUST** enforce module isolation. No `use Modules\B\Models\User` in `Modules\A`.
- **MUST** use `final` and `readonly` for pattern classes.
- **MUST** provide architectural reasoning for chosen patterns.
