---
name: api-skill
description: "Encodes opinionated best practices for building REST APIs in Laravel 13+. Enforces Single-Action Controllers, Versioned Payloads, Actions, and RFC 9457 error handling."
---

# API Skill for Laravel

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel. All guidance here is prescriptive. When in doubt, follow the rule.

---

## 1. Route Organisation & Versioning

- **Standalone Modular Architecture**: Routes live under `modules/{Module}/Routes/v1.php`.
- **Mandatory Versioning**: Always version endpoints from the beginning.
- **Middleware Stack**: Every group must use: `force.json` (Required first), `auth:sanctum` (if protected), and `throttle:api` (Mandatory for all).
- **Sunset Header**: Use RFC 8594 `Sunset` header for deprecated versions to signal removal dates.

## 2. Versioned Components

To ensure smooth API evolution, the following must be placed in versioned directories:
- **Controllers**: `modules/{Module}/Controllers/V1/`
- **Payloads**: `modules/{Module}/Payloads/V1/` (Renamed from DTOs)
- **Form Requests**: `modules/{Module}/Requests/V1/`
- **Tests**: `modules/{Module}/Tests/Feature/V1/`

## 3. Single-Action Invokable Controllers

- **Final Class**: Every controller must be a `final` class.
- **One Class, One Action**: Use only the `__invoke()` method. No resourceful or multi-method controllers.
- **Constructor DI**: Inject all dependencies via the constructor. Never use `app()`, `resolve()`, or Facades inside methods.

## 4. The Action Pattern

- **Logic Placement**: Business logic lives in **Action classes** under `modules/{Module}/Actions/`.
- **Atomic Operations**: One action per database operation.
- **Transactions**: Every action that writes to the database must be wrapped in a transaction using injected `DatabaseManager`.
- **Eloquent Usage**: Call Eloquent models directly within Actions. Discourage unnecessary Repository layers for simple CRUD.

## 5. Standardized Responses

- **Success**: Use `new JsonDataResponse(data: $resource, status: $status)` directly in controllers. No trait or base controller required.
- **Error**: Strict **RFC 9457 Problem Details** via `new ProblemResponse(...)` for all exceptions. Content-Type is automatically set to `application/problem+json`.
- **Status Codes**: Mandatory use of Symfony `Response::HTTP_*` constants. Never bare integers.

## 6. Security & Models

- **Authentication**: Stateless Laravel Sanctum tokens only. No session-based auth.
- **Authorization**: Use Laravel Policies. Checks belong in the Form Request `authorize()` method.
- **Flexible Identifiers**: Models support Integer, UUID, or ULID primary keys.
- **Strict Mode**: `Model::shouldBeStrict(!app()->isProduction())` enabled in development.

## 7. Performance & Throttling

- **Pagination**: Use `simplePaginate()` only. Never `paginate()` on API routes.
- **Rate Limiting**: Every route group must have `throttle:api`. Define limiters in `AppServiceProvider`.
- **Query Filtering**: Use the custom `BaseFilter` system for search/sort/filter.

## 8. Code Quality & Testing

- **Pest PHP**: Use Pest for outside-in HTTP/Feature tests, drive tests through the API layer.
- **Strict Standards**: Mandatory `declare(strict_types=1)`, `final` classes, and full type coverage.
- **Modern PHP**: Use `match` expressions over `if/elseif` and **Named Arguments** for readability.

---

## Usage

AI agents must read [references/CONVENTIONS.md](references/CONVENTIONS.md) for full folder structures, naming tables, implementation details, and copy-pasteable examples.

## Anti-Patterns

- ❌ No Resourceful Controllers or Logic in Models.
- ❌ No `DTO` suffix (use **Payload** instead).
- ❌ No `paginate()` or unthrottled routes.
- ❌ No `ApiResponser` trait or `$this->successResponse()` (use `new JsonDataResponse()`).
- ❌ No `response()->json()` calls for errors (use `new ProblemResponse()`).
- ❌ No bare integer HTTP status codes (use `Response::HTTP_*` constants).
- ❌ No authorization checks inside Actions (move to Form Requests).
- ❌ No manual dependency resolution inside methods.
