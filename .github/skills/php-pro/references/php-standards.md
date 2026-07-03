# PHP 8.4 & Modern Standards

This document defines the professional PHP standards for the Standard 2026 project.

## 1. Strict Typing and Safety

- **declare(strict_types=1)**: Mandatory at the top of every PHP file.
- **DNF Types**: Use Disjunctive Normal Form types where appropriate (e.g., `(HasEmail&HasName)|Guest`).
- **No Mixed**: Avoid `mixed` type. If a type is unknown, use specific interfaces or nullable types.
- **Return Types**: All methods MUST have explicit return types, including `void`.

## 2. Immutability & Class Design

- **Final Classes**: All classes must be marked `final` to prevent inheritance-related bugs.
- **Readonly Classes**: Use `final readonly class` for DTOs/Payloads that do not require property hooks.
- **Constructor Promotion**: Mandatory for injecting dependencies and defining properties in one go.

```php
public function __construct(
    public readonly string $uuid,
    private readonly DatabaseManager $database,
) {}
```

## 3. Property Hooks (PHP 8.4)

Mandatory for Payloads that require data sanitization.

- Use `set` for transformation (e.g., `trim`, `strtolower`, `ucfirst`).
- Use `get` for computed properties.
- **Restriction**: Do not use property hooks to perform database queries or heavy I/O.

## 4. Enums & Constants

- **Backed Enums**: Always use string-backed or int-backed enums for statuses and types.
- **Naming**: Use `TitleCase` for Enum keys (e.g., `Status::Active`).
- **Exhaustiveness**: Use `match` expressions to ensure all Enum cases are handled.

## 5. Naming Conventions

- **Variables/Methods**: Descriptive camelCase (e.g., `isRegisteredForDiscounts()`).
- **Booleans**: Prefix with `is`, `has`, `should`, or `can`.
- **Arrays**: If an array contains a specific type, document it via PHPDoc: `/** @var array<int, User> $users */`.
