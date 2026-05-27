---
name: laravel-boost-core
description: "Core PHP 8.4+ standards and conventions for Laravel Boost projects."
metadata:
  version: "1.0.0"
  triggers: "PHP, declare(strict_types=1), Property Hooks, Payloads, final readonly"
---

# Laravel Boost Core

Ensures the foundational PHP 8.4 code quality and conventions.

## Standards
- **Strict Types**: Every file MUST start with `declare(strict_types=1);`.
- **Final & Readonly**: All classes MUST be `final`. Controllers and Actions MUST be `readonly`.
- **PHP 8.4 Property Hooks**: Mandatory in **Payloads** for data transformation.
- **Constructor Promotion**: Use PHP 8 constructor property promotion.
- **Type Hinting**: Explicit return types and parameter types for ALL methods.

## Example Payload
```php
final class UpdateUserPayload
{
    public string $name {
        set => trim($value);
    }

    public function __construct(
        public string $name,
        public ?string $bio = null,
    ) {}
}
```
