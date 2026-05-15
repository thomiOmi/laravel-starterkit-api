---
name: api-skill
description: "Encodes enterprise-grade patterns for building REST APIs in Laravel 13+. Enforces Single-Action Controllers, Payloads, and RFC 9457 error handling."
---

# API Skill for Laravel

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel. All guidance here is prescriptive. When in doubt, follow the rule.

---

## 1. Route Organisation & Versioning

- **Modular Structure**: Routes live under `modules/{Module}/Routes/v1.php`.
- **Mandatory Versioning**: Always version endpoints from the beginning.
- **Middleware Stack**: Every group must use: `force.json` (Accept header), `auth:sanctum` (if protected), and `throttle:api`.
- **Sunset Header**: Use RFC 8594 `Sunset` header for deprecated routes.

## 2. Single-Action Invokable Controllers

- **Final Only**: Every controller is a `final` single-action class.
- **Invokable**: Use the `__invoke()` method. No resourceful or multi-method controllers.
- **Constructor DI**: Always inject dependencies via the constructor. No `app()`, `resolve()`, or Facades inside methods.

## 3. Form Requests & Payloads

- **Payload Pattern**: Use **Payloads** (Typed PHP objects) instead of traditional DTOs.
- **Validation**: Every state-mutating endpoint uses a Form Request.
- **Payload Extraction**: Form Requests must expose a `payload()` method returning a typed Payload class.

## 4. The Action Pattern

- **Logic Placement**: Business logic lives in **Action classes** under `modules/{Module}/Actions/`.
- **One per Operation**: One action per database operation.
- **Transactions**: Every action that writes to the database must be wrapped in a transaction using injected `DatabaseManager`.
- **Eloquent Usage**: Call Eloquent models directly within Actions. Discourage unnecessary Repository layers.

## 5. Success & Error Responses

- **Success**: Use **Eloquent Resources** and the `ApiResponser` success helpers.
- **Error Handling**: Follow **RFC 9457 Problem Details**. Use `ProblemResponse` to ensure consistent JSON formatting.
- **Status Codes**: Always use Symfony `Response::HTTP_*` constants. Never bare integers.

## 6. Security & Models

- **Authentication**: Stateless Laravel Sanctum tokens only.
- **Authorization**: Use Spatie Laravel Permission (RBAC). Check permissions in Form Request `authorize()`.
- **Flexible Identifiers**: Models can use Integer, UUID, or ULID primary keys.
- **Strict Mode**: `Model::shouldBeStrict(!app()->isProduction())` enabled in development.

## 7. Performance & Throttling

- **Pagination**: Use `simplePaginate()` only. Never `paginate()` on API routes.
- **Rate Limiting**: Every route group must have `throttle:api`. Define limiters in `AppServiceProvider`.
- **Query Filtering**: Use the custom `BaseFilter` system for search/sort/filter.

## 8. Code Quality & Testing

- **Pest PHP**: Use Pest for outside-in HTTP/Feature tests.
- **Strict Typing**: Mandatory `declare(strict_types=1)` and full type coverage.
- **Final Classes**: Use `final` by default for all infrastructure and logic classes.

---

## Usage

AI agents must read [references/CONVENTIONS.md](references/CONVENTIONS.md) for full folder structures, naming tables, and copy-pasteable worked examples.

## Anti-Patterns

- ❌ No Resourceful Controllers.
- ❌ No `DTO` suffix (use Payload instead).
- ❌ No logic in Models or Controllers.
- ❌ No unthrottled routes or HTML error responses.
- ❌ No Spatie Query Builder (use `BaseFilter`).
- ❌ No manual dependency resolution inside methods.
