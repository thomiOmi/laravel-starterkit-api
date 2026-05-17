---
name: api-skill
description: "Encodes opinionated best practices for building REST APIs in Laravel 13+. Enforces Single-Action Controllers, Versioned Payloads, Actions, and RFC 9457 error handling."
---

# API Skill for Laravel

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel. All guidance here is prescriptive. When in doubt, follow the rule.

---

## 1. Route Organisation & Versioning

- **Standalone Modular Architecture**: Routes live under `modules/{Module}/Routes/V1.php`. Note the uppercase `V1`.
- **Mandatory Versioning**: Always version endpoints from the beginning (e.g., `/api/V1/...`).
- **Middleware Stack**: Every group must use: `force.json` (Required first), `auth:sanctum` (if protected), and `throttle:api` (Mandatory for all).
- **Sunset Header**: Use RFC 8594 `Sunset` header for deprecated versions to signal removal dates.

## 2. Versioned Components

To ensure smooth API evolution, the following must be placed in versioned directories (Always uppercase `V1`):
- **Controllers**: `modules/{Module}/Controllers/V1/`
- **Payloads**: `modules/{Module}/Payloads/V1/`
- **Form Requests**: `modules/{Module}/Requests/V1/`
- **Tests**: `modules/{Module}/Tests/Feature/V1/`
- **Routes**: `modules/{Module}/Routes/V1.php`

## 3. Single-Action Invokable Controllers

- **Final Class**: Every controller must be a `final` class.
- **One Class, One Action**: Use only the `__invoke()` method. No resourceful or multi-method controllers.
- **Constructor DI**: Inject all dependencies via the constructor. Never use `app()`, `resolve()`, or Facades inside methods.

## 4. The Action Pattern & Composition

- **Logic Placement**: Business logic lives in **Action classes** under `modules/{Module}/Actions/`.
- **Atomic Operations**: One action per database operation.
- **Action Composition (Orchestrator)**: For complex flows (e.g., Checkout), create a main Action that calls multiple atomic Actions. Avoid bloated single Action files.
- **Transactions**: Every action that writes to the database must be wrapped in a transaction using injected `DatabaseManager`.
- **Eloquent Usage**: Call Eloquent models directly within Actions. Discourage unnecessary Repository layers for simple CRUD.

## 5. Modular Communication (Cross-Module)

- **Events for Side-Effects**: Use Laravel Events/Listeners to trigger actions in other modules (e.g., `OrderCreated` triggers `UpdateInventory`).
- **Direct Reads for Models**: It is acceptable for one module to read another module's Model directly for data retrieval to avoid over-engineering with Service layers.
- **No Circular Dependencies**: Ensure Module A doesn't depend on B while B depends on A.

## 6. Standardized Responses & Documentation

- **Success**: Use `new JsonDataResponse(data: $resource, status: $status)` directly in controllers.
- **Error**: Strict **RFC 9457 Problem Details** via `new ProblemResponse(...)` for all exceptions.
- **Status Codes**: Mandatory use of Symfony `Response::HTTP_*` constants.
- **PHPDoc & Scribe**: Every class and method MUST have detailed PHPDoc (type hints, descriptions, `@param`, `@return`, `@throws`). This enables Scribe to generate accurate documentation.

## 7. Security & Models

- **Authentication**: Stateless Laravel Sanctum tokens only. No session-based auth.
- **Authorization**: Use Laravel Policies. Checks belong in the Form Request `authorize()` method.
- **Factories**: Always use Factories for data seeding and testing.
- **Strict Mode**: `Model::shouldBeStrict(!app()->isProduction())` enabled in development.

## 8. Performance & Throttling

- **Pagination**: Use `simplePaginate()` only. Never `paginate()` on API routes.
- **Rate Limiting**: Every route group must have `throttle:api`. Define limiters in `AppServiceProvider`.
- **Query Filtering**: Use the custom `BaseFilter` system for search/sort/filter.

## 9. Code Quality & Testing

- **Pest PHP**: Use Pest for outside-in HTTP/Feature tests. Use Factories for all data setup.
- **Strict Standards**: Mandatory `declare(strict_types=1)`, `final` classes, and full type coverage.
- **Modern PHP**: Use `match` expressions, Constructor Property Promotion, and **Named Arguments**.

---

## Usage

AI agents must read [references/CONVENTIONS.md](references/CONVENTIONS.md) for full folder structures, naming tables, implementation details, and copy-pasteable examples.

## Anti-Patterns

- ❌ No Resourceful Controllers or Logic in Models.
- ❌ No `DTO` suffix (use **Payload** instead).
- ❌ No `paginate()` or unthrottled routes.
- ❌ No logic in Controllers (move to Actions).
- ❌ No authorization checks inside Actions (move to Form Requests).
- ❌ No lowercase `v1` for versioned folders or files.
- ❌ No circular module dependencies.
- ❌ No missing PHPDocs on public methods.
- ❌ No manual transaction handling via Facades (use injected `DatabaseManager`).
