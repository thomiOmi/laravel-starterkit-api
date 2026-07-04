---
name: php-pro
description: Advanced PHP 8.4 patterns including Property Hooks, strict typing, and functional programming. Use for core logic, DTOs, and complex data structures.
license: MIT
metadata:
  version: "2.0.0"
---

# PHP 8.4 Professional Standards

Expert-level PHP development with a focus on type safety and modern syntax.

## Modern PHP 8.4 Features

### 1. Property Hooks
Replace verbose getters/setters.
```php
public string $email {
    set => strtolower($value);
    get => $this->email;
}
```

### 2. Final by Default
Protect class hierarchies from unintended extension.
```php
final readonly class CreateUserPayload { ... }
```

### 3. Strict Typing
Always use `declare(strict_types=1);` and native types for EVERYTHING.

## Functional Patterns
- Use `array_map`, `array_filter` with explicit closures.
- Use `match` expressions over `switch`.
- Leverage Laravel Collections for complex data transformation.

## Constraints
- **MUST** use Property Hooks for derived data.
- **MUST** use Constructor Property Promotion.
- **MUST** use Enums for status/type fields.
- **MUST** pass PHPStan Level 9 or max available.
