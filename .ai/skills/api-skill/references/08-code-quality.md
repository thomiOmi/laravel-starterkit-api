# Code Quality & General Standards

Every PHP file in this project must adhere to high-quality standards to ensure consistency, readability, and type safety.

## 1. File Header

Every file must start with `declare(strict_types=1)` immediately after the opening PHP tag.

```php
<?php

declare(strict_types=1);
```

## 2. Class Standards

- **Final by Default**: All classes (Controllers, Actions, Payloads, Jobs, Middleware, Resources) should be `final` unless they are explicitly designed for inheritance.
- **PSR-12**: Follow PSR-12 coding style conventions.
- **Strict Typing**:
    - Every method must have return type hints.
    - Every parameter must have type hints.
    - Properties must be typed.

## 3. Modern PHP Features

- **Match Expression**: Use `match` instead of complex `if/elseif` or `switch` statements for value assignment.
- **Constructor Property Promotion**: Use it to keep classes lean.
- **Named Arguments**: Use named arguments when calling methods with multiple parameters, especially booleans or nulls, to improve readability.
- **Readonly Classes/Properties**: Use `readonly` for immutable objects like Payloads and DTOs.

## 4. Anti-Patterns

- ❌ Do not omit `declare(strict_types=1)`.
- ❌ Do not use `mixed` type unless absolutely necessary.
- ❌ Do not use "magic" helpers like `config('app.name')` inside deep business logic; inject values or use a Service/Action instead.
- ❌ Do not leave unused imports (`use` statements).
- ❌ Do not commit commented-out code.
- ❌ Do not use abbreviations for variable names (e.g., use `$user` instead of `$u`).
