---
name: php-pro
description: "Expert PHP 8.4+ development with strict typing, modern patterns, and Property Hooks."
metadata:
  version: "1.1.0"
  triggers: "PHP, PHP 8.4, Property Hooks, declare(strict_types=1), final readonly, DTO, Payload"
---

# PHP Pro (Standard 2026)

This skill enforces high-standard PHP development, leveraging the latest features of PHP 8.4 to ensure type safety, immutability, and clean data handling.

## 1. Core Principles
- **Strict Typing**: Every file MUST start with `declare(strict_types=1);`.
- **Immutability**: Prefer `readonly` classes and properties. All classes MUST be `final`.
- **Constructor Promotion**: Always use constructor property promotion for dependency injection and data objects.
- **Explicit Types**: Never use `mixed`. Always type hint parameters, return types, and properties.

## 2. PHP 8.4 Property Hooks
Mandatory for **Payloads** (DTOs) to handle data transformation and validation directly on properties. This reduces boilerplate in controllers and actions.

### Implementation Guide
```php
final class CreateUserPayload
{
    public string $email {
        set => strtolower(trim($value));
        get => $this->email;
    }

    public string $name {
        set(string $value) {
            if (strlen($value) < 3) {
                throw new \InvalidArgumentException("Name too short");
            }
            $this->name = ucwords(trim($value));
        }
    }

    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
```

## 3. Class Architecture
- **Controllers & Actions**: MUST be `final readonly`.
- **Payloads & Resources**: MUST be `final`.
- **Interfaces**: Use interfaces for cross-module communication or when multiple implementations exist.

## 4. Modern Patterns
- **Enums**: Use Backed Enums for status and type fields.
- **Match Expressions**: Use `match` over `switch` for exhaustive checks.
- **Attributes**: Use PHP Attributes instead of PHPDoc annotations where possible (e.g., for API documentation).

## 5. Verification Checklist
- [ ] `declare(strict_types=1);` present?
- [ ] All classes marked `final`?
- [ ] Payloads using Property Hooks?
- [ ] No `mixed` types used?
- [ ] PHPDoc only used for complex logic explanation?
