---
name: php-pro
description: Expert PHP 8.4 patterns including Property Hooks, Strict Typing, and Immutability. Handles complex DTOs, Enums, and functional data processing.
license: MIT
metadata:
  version: "2.3.0"
---

# PHP 8.4 Professional Standards

Leverage modern PHP syntax to write safe, expressive, and high-performance code.

## 1. Property Hooks (The Standard)
Mandatory for all derived or mutated logic within Models and Payloads.

```php
final class Order
{
    /**
     * Calculated virtual property
     */
    public int $totalCents {
        get => $this->items->sum('price_cents') + $this->shipping_cents;
    }

    /**
     * Mutated backed property
     */
    public string $trackingNumber {
        set(string $value) => strtoupper(trim($value));
        get => $this->tracking_number;
    }
}
```

## 2. Advanced Immutability
Use `final readonly` for all data-carrying classes (DTOs, Payloads, Value Objects).

```php
final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    public function toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }
}
```

## 3. Strict Quality Checklist
- **declare(strict_types=1):** Must be present in every file.
- **Native Types:** No `mixed`, no untyped properties. Use Union Types or Intersection Types if necessary.
- **Enums:** Use Backed Enums for any set of fixed values (Statuses, Types, Roles).

## Verification Loop
1. Run `phpstan` at max level.
2. Check for "mixed" usage; eliminate all of them.
3. Ensure all class properties have native type hints.

## Constraints
- **MUST** use Property Hooks for all accessors/mutators.
- **MUST** use Constructor Property Promotion.
- **MUST NOT** use `@phpstan-ignore`.
