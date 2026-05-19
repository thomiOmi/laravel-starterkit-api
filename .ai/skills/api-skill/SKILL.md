---
name: api-skill
description: "Opinionated agent skill for building production-ready REST APIs in Laravel 13+ (Standard 2026)."
---

# API Skill for Laravel (Standard 2026)

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel 13+. All guidance here is prescriptive.

---

## 1. Architectural Foundations

- **Domain-Driven Modular Architecture**: All logic resides in `modules/{Module}/`.
- **Stateless by Design**: APIs must be stateless. No sessions, only Sanctum tokens.
- **Strict Models**: `Model::shouldBeStrict(!app()->isProduction())` must be enabled in `AppServiceProvider`.
- **Flexible Identifiers**: Support for Integer, UUID, or ULID. Choose one and be consistent within a module.

## 2. PHP 8.4 & Modern Patterns

- **Property Hooks**: Mandatory use in **Payloads** for simple data transformations (e.g., `trim`, `strtolower`) to reduce boilerplate.
- **Final & Readonly**: All classes (Controllers, Actions, Payloads, Services) must be `final`. Actions and Controllers must be `readonly`.
- **Strict Types**: Every file must start with `declare(strict_types=1);`.

## 3. Directory Structure (Uppercase V1)

Module structure follows this pattern:
- **Routes**: `modules/{Module}/Routes/V1.php`
- **Controllers**: `modules/{Module}/Controllers/V1/`
- **Payloads**: `modules/{Module}/Payloads/V1/`
- **Requests**: `modules/{Module}/Requests/V1/`
- **Actions**: `modules/{Module}/Actions/`
- **Filters**: `modules/{Module}/Filters/`
- **Resources**: `modules/{Module}/Resources/`
- **Tests**: `modules/{Module}/Tests/Feature/V1/` & `modules/{Module}/Tests/Architecture/`

## 4. Request Lifecycle & Observability

1.  **Trace ID**: Every request must have a `trace_id` managed via Laravel **Context**. The `trace_id` must appear in the response header (`X-Trace-ID`) and every log line.
2.  **Force JSON**: Use the `ForceJsonResponse` middleware to ensure all responses are JSON.
3.  **Throttling**: Mandatory use of `throttle:api` on all routes.

## 5. Single-Action Controllers

- **Invokable Only**: Use only `__invoke()`.
- **Dependency Injection**: Use constructor injection. Direct use of Facades or the `app()` helper inside controllers is prohibited.
- **Attributes Over DocBlocks**: Use **PHP Attributes** for Scribe documentation and other metadata.

## 6. The Action Pattern & Laravel 13 Features

- **Atomic Actions**: One action per database operation or specific business rule.
- **Orchestrator**: Use an orchestrator action for complex workflows.
- **Transactions**: Mandatory use of `$database->transaction()` for all write operations.
- **Defer & Concurrency**:
    - Use `defer()` for non-critical post-response tasks (e.g., sending emails/notifications).
    - Use `Concurrency::run()` to execute independent I/O bound tasks in parallel.

## 7. Payloads (DTOs) with Property Hooks

Payloads are data objects passed from Requests to Actions.
- Use **Property Hooks** for data sanitation.
- Avoid manual array manipulation in controllers.

## 8. Filtering (BaseFilter)

- **No Third-Party Query Builders**: Use the internal `BaseFilter` system.
- **Implementation**: Filter classes must extend `BaseFilter<Model>` to ensure Type Safety.

## 9. Automated Quality (Pest Arch)

- **Architecture Testing**: Mandatory inclusion of Pest Arch to validate:
    - No direct Model access from Controllers.
    - All Controllers & Actions are `final`.
    - No `env()` usage outside configuration files.

## 10. Standardized Responses

- **Success**: Use `JsonDataResponse`.
- **Error**: RFC 9457 Problem Details via `ProblemResponse`.
- **HTTP Status**: Use `Symfony\Component\HttpFoundation\Response::HTTP_*` constants.

---

## Anti-Patterns

- ❌ No logic in Models (except scopes).
- ❌ No Multi-action Controllers.
- ❌ Never skip `declare(strict_types=1);`.
- ❌ No `app()` helper or Facades if injection is possible.
- ❌ Never return raw models or arrays (must use API Resources).
