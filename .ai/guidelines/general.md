# Laravel Starterkit Coding Guidelines (The Law)

These rules are non-negotiable for all development within this repository. AI agents and human developers must adhere to these foundations to ensure scalability and maintainability.

## 1. Architectural Foundations
- **Modular Monolith:** The application is organized into modules within `modules/`. Each module is self-contained.
- **Module Isolation:** Modules MUST NOT import Models or internal classes from other modules directly.
  - *Exception:* Sharing via `app/Contracts` (Interfaces) or communicating via `Events`.
- **Final by Default:** All classes (Controllers, Actions, Services, Payloads) MUST be declared as `final`.
- **No-Ignore Policy:** NEVER use `@phpstan-ignore`, `@noinspection`, or suppress any linting/static analysis errors. Fix the underlying code issue instead.

## 2. Business Logic Pattern (Action-Payload)
- **Thin Controllers:** Controllers only transform the Request into a Payload and delegate to an Action.
- **Action Mandate:** All business logic MUST reside in a single-purpose Action class.
- **Payload Mandate:** Actions MUST receive a `final readonly` Payload (DTO) class. Never pass raw arrays or Request objects to an Action.
- **Property Hooks:** Use PHP 8.4 Property Hooks for all derived logic, accessors, and mutators in Models and Payloads.

## 3. API Excellence
- **Error Standard:** Use **RFC 9457 (ProblemResponse)** for all non-2xx responses.
- **Contract Stability:** Always use `JsonResource` for response transformation to decouple DB schema from API contracts.
- **Idempotency:** Support `Idempotency-Key` for sensitive state-changing operations.
- **Type Safety:** Use strict typing for all properties, parameters, and return types. Avoid `mixed` at all costs.

## 4. Verification Loop
Before declaring a task complete, you MUST:
1. Run `./vendor/bin/pint --format agent` to fix styling.
2. Run `./vendor/bin/phpstan analyse` and ensure 0 errors.
3. Run `php artisan test --compact` and ensure all tests pass.
4. Use the `database-schema` MCP tool to verify any migration changes.

## 5. File Ownership
- **AGENTS.md**: Auto-generated. DO NOT EDIT.
- **.ai/guidelines/**: Source of truth for guidelines.
- **.ai/skills/**: Source of truth for domain-specific expertise.
