---
name: api-skill
description: "Enterprise REST API standards for Laravel 13+. Enforces Single-Action Controllers, Payloads, RFC 9457 Errors, and Modular Domain Architecture."
---

# API Skill for Laravel

This skill defines the prescriptive patterns for building scalable and modern REST APIs. AI agents must follow these rules without deviation.

---

## 1. Modular Architecture & Routing

- **Domain-Driven**: All logic resides in `modules/{Module}/`.
- **Versioned Routes**: Defined in `modules/{Module}/Routes/v1.php`.
- **Standard Stack**: `force.json` -> `auth:sanctum` -> `throttle:api`.
- **Sunset Header**: Required for deprecated endpoints (RFC 8594).

## 2. Controllers & Dependency Injection

- **Single-Action Only**: Every controller must be a `final` class with only the `__invoke()` method.
- **Explicit DI**: Dependencies must be injected via the constructor. No `app()` or Facades in methods.

## 3. Form Requests & Payloads

- **Payload Pattern**: Use **Payloads** (Typed immutable objects) for data transfer. No "DTO" suffix.
- **Method**: Form Requests must implement a `payload()` method.
- **Versioning**: Both Requests and Payloads must be in versioned directories (e.g., `V1/`).

## 4. Business Logic (Actions)

- **One per Operation**: Encapsulate logic in **Action classes**.
- **Eloquent usage**: Call models directly within Actions. Discourage unnecessary Repositories.
- **Transactions**: Mandatory for all database writes using injected `DatabaseManager`.

## 5. Standardized Responses

- **Success**: Consistent JSON envelope via `ApiResponser` trait and **Eloquent Resources**.
- **Error**: Strict **RFC 9457 Problem Details** for all exceptions.
- **Status Codes**: Mandatory use of Symfony `Response::HTTP_*` constants.

## 6. Models & Security

- **Flexible IDs**: Supports Integer, UUID, or ULID primary keys.
- **Strict Mode**: `Model::shouldBeStrict(!app()->isProduction())` required in development.
- **RBAC**: Spatie Laravel Permission integrated with Laravel Policies.

## 7. Performance & Throttling

- **Lean Pagination**: Always use `simplePaginate()`. Never `paginate()`.
- **Global Throttling**: Every group must have `throttle:api`.
- **Filtering**: Use the internal `BaseFilter` system for search/sort/filter.

## 8. Quality & Documentation

- **Test Driven**: Outside-in HTTP/Feature tests using **Pest PHP**.
- **Strict Types**: Mandatory `declare(strict_types=1)` and full type coverage.
- **Auto-Docs**: Comprehensive **Scramble** attributes for parameters and tags.

---

## Usage

AI agents must consult [references/CONVENTIONS.md](references/CONVENTIONS.md) for detailed folder structures, naming tables, and implementation boilerplate.

## Prohibited Patterns (Anti-Patterns)

- ❌ No Resourceful Controllers or Logic in Models.
- ❌ No `DTO` suffix (use **Payload**).
- ❌ No `paginate()` (use `simplePaginate`).
- ❌ No unthrottled routes or HTML error responses.
- ❌ No authorization checks inside Actions (use Form Requests).
- ❌ No manual dependency resolution inside methods.
