# Code Quality Standards

This document defines the technical standards for writing PHP code in this project.

---

## 1. Type Safety & Strictness

- **Declare Strict Types**: Every file must start with `declare(strict_types=1);`.
- **Property Promotion**: Use constructor property promotion whenever possible.
- **Type Hinting**: All properties, parameters, and return values must be strictly typed.
- **Readonly Classes**: All Actions, Payloads, and Controllers must be `final readonly`.

## 2. Naming & Documentation

- **Expressive Naming**: Class names must follow the `{Action}{Resource}{Type}` pattern.
- **PHPDoc Requirements**:
    - Every public method must have a PHPDoc block.
    - Document `@param`, `@return`, and `@throws`.
    - Use `@group` and `@authenticated` for Scribe documentation.
- **Symfony Constants**: Always use `Symfony\Component\HttpFoundation\Response` constants for HTTP status codes.

## 3. Business Logic (Actions)

- **One Responsibility**: An action performs exactly one task.
- **Composition over Bloat**: If an action is too large, break it into smaller sub-actions and use an orchestrator.
- **No Side Effects in Actions**: Use Events to trigger emails, notifications, or external API calls.
- **Transactions**: Use `$database->transaction()` for all write operations.

## 4. Modern PHP Features

- **Match Expressions**: Use `match` instead of complex `switch` or `if/else`.
- **Null-safe Operator**: Use `$object?->property`.
- **Named Arguments**: Use named arguments for clarity when calling methods with multiple parameters.

## 5. Tooling

- **Pint**: Run `./vendor/bin/pint --format agent` to fix styling.
- **PHPStan**: All code must pass PHPStan analysis at the configured level.
