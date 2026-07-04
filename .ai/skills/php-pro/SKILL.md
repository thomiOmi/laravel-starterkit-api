---
name: php-pro
description: Expert PHP 8.4 implementation including Property Hooks, strict typing, and functional patterns. Use for core logic, DTOs, and complex data transformations.
license: MIT
metadata:
  version: "2.2.0"
---

# PHP 8.4 Professional Standards

Leverage the full power of modern PHP to write expressive, fast, and safe code.

## 1. Property Hooks (Mandatory for derived logic)
Native alternative to Laravel Attributes or getters/setters.

```php
final class User
{
    /**
     * Virtual property example
     */
    public string $fullName {
        get => "{$this->first_name} {$this->last_name}";
    }

    /**
     * Mutator example
     */
    public string $password {
        set(string $value) => Hash::make($value);
    }
}
```

## 2. Strict Immutability (Final & Readonly)
Ensure your data structures are predictable.

```php
final readonly class CreateUserPayload
{
    public function __construct(
        public string $name,
        public string $email,
        public UserRole $role = UserRole::Member,
    ) {}
}
```

## 3. High-Level Type Safety
- Use **Enums** for all fixed sets of values.
- Use **Intersection Types** (`Countable&ArrayAccess`) for complex requirements.
- Use **Readonly Classes** for DTOs/Payloads.

## Constraints
- **MUST** use `declare(strict_types=1);` in all new files.
- **MUST** use Property Hooks for all calculated fields.
- **MUST** use Enums instead of string constants.
- **MUST NOT** use `mixed` type. Use specific types or union types.

## Verification
1. Run `phpstan` at max level.
2. Check for "Property Type Coverage" to ensure all class properties are typed.
