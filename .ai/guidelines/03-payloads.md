# Payload (DTO) Standards

We use **Payloads** (formerly referred to as DTOs) to transfer data between the Request layer and the Action layer. This ensures a strict data contract and keeps our controllers and actions clean.

## 1. Core Principles

- **Immutability**: Payloads should be `readonly` classes.
- **Strict Typing**: All properties and constructor parameters must be typed.
- **Conversion**: Every Payload should have a `toArray()` method for Eloquent consumption.
- **Naming**: Use the suffix `Payload` (e.g., `StoreUserPayload`).
- **Placement**: Place them in `modules/{Module}/Payloads/`.

## 2. Implementation Example

### The Payload Class (`modules/User/Payloads/StoreUserPayload.php`)

```php
<?php

declare(strict_types=1);

namespace Modules\User\Payloads;

final readonly class StoreUserPayload
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public array $roles = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
        ];
    }
}
```

### The Form Request Integration (`modules/User/Requests/UserRequest.php`)

Every state-mutating Form Request must expose a `payload()` method:

```php
public function payload(): StoreUserPayload
{
    return new StoreUserPayload(
        name:     $this->string('name')->toString(),
        email:    $this->string('email')->toString(),
        password: $this->string('password')->toString(),
        roles:    $this->input('roles', []),
    );
}
```

## 3. Why use Payloads?

- **Explicit Contracts**: You know exactly what data an Action expects.
- **Decoupling**: The Action doesn't need to know about the `Request` object or its internal structure.
- **Type Safety**: Prevents "array-shape" issues and undefined key errors.
- **Refactoring Ease**: Changing a field name only requires updating the Form Request and the Payload, rather than searching through all business logic.

## 4. Anti-Patterns

- ❌ Do not pass the `Request` object directly into an Action.
- ❌ Do not pass raw associative arrays between layers.
- ❌ Do not use the term `DTO` in new code; use `Payload` instead.
- ❌ Do not put business logic inside a Payload; it should be a data carrier only.
