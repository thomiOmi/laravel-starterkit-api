---
name: api-skill
description: "Opinionated agent skill for building production-ready REST APIs in Laravel 13+."
---

# API Skill for Laravel

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel 13+. All guidance here is prescriptive. When in doubt, follow the rule.

---

## 1. Architectural Foundations

- **Domain-Driven Modular Architecture**: All logic resides in `modules/{Module}/`.
- **Stateless by Design**: APIs must be stateless. No sessions, only Sanctum tokens.
- **Strict Models**: `Model::shouldBeStrict(!app()->isProduction())` must be enabled in `AppServiceProvider`.
- **Flexible Identifiers**: Support for Integer, UUID, or ULID. Be consistent within a module.

## 2. Directory Structure (Uppercase V1)

Adhere to this structure for every module:
- **Routes**: `modules/{Module}/Routes/V1.php`
- **Controllers**: `modules/{Module}/Controllers/V1/`
- **Payloads**: `modules/{Module}/Payloads/V1/` (Renamed from DTOs)
- **Form Requests**: `modules/{Module}/Requests/V1/`
- **Tests**: `modules/{Module}/Tests/Feature/V1/`
- **Actions**: `modules/{Module}/Actions/`
- **Models/Resources/Filters**: Standard modular subdirectories.

## 3. Request Lifecycle

1.  **Route**: Versioned, throttled, and forced JSON.
2.  **Form Request**: Handles validation and authorization (via Policies).
3.  **Controller**: Single-action (`__invoke`), injects Action, returns `JsonDataResponse`.
4.  **Payload**: Type-safe data object passed from Request to Action.
5.  **Action**: Business logic orchestrator or atomic operation.
6.  **Resource**: Shapes the JSON output.

## 4. Single-Action Controllers

- **Final & Readonly**: Every controller must be a `final readonly` class.
- **Invokable Only**: Use only `__invoke()`. No multi-method controllers.
- **Constructor Injection**: Inject all dependencies. Never use Facades or `app()` helpers.

## 5. The Action Pattern

- **Atomic Actions**: One action per database write or specific business rule.
- **Action Composition**: Use an orchestrator action to coordinate multiple atomic actions for complex flows.
- **Transactions**: Mandatory use of `$database->transaction()` for all write operations.
- **No Direct Eloquent in Controllers**: All DB writes must happen in Actions.

## 6. Authorization (Spatie Permission)

- **Policies**: Every resource must have a Policy.
- **Sanctum + Spatie**: Use `$user->can()` or `$user->hasPermissionTo()` within Policies.
- **Gate Integration**: Ensure Super Admin bypass is handled via `Gate::before` in `AuthServiceProvider`.
- **Form Request Auth**: Authorization checks belong in the `authorize()` method of Form Requests.

## 7. Filtering & Sorting (BaseFilter)

- **No Spatie Query Builder**: Use the internal `BaseFilter` system.
- **Implementation**: Extend `BaseFilter<Model>` and implement the `apply` logic for each filterable field.

## 8. Standardized Responses & Documentation

- **Success**: Use `new JsonDataResponse(data: $resource, status: $status, message: $msg)`.
- **Error**: RFC 9457 Problem Details via `new ProblemResponse(...)`.
- **PHPDoc**: Mandatory detailed PHPDoc on all classes and public methods for **Scribe** documentation generation.
- **Status Codes**: Use Symfony `Response::HTTP_*` constants. No bare integers.

---

## Anti-Patterns

- ❌ No Resourceful Controllers or logic in Models.
- ❌ No `DTO` suffix (use **Payload** instead).
- ❌ No logic in Controllers (move to Actions).
- ❌ No manual `response()->json()` for standard responses.
- ❌ No lowercase `v1` for versioned paths or namespaces.
- ❌ No unthrottled or session-based API routes.
- ❌ No direct permission checks in Controllers (use Policies).
