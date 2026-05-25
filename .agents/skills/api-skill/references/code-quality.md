# Code Quality & Standards (2026)

Every PHP file in the project must meet these standards without exception.

---

## 1. PHP 8.4 Standards

- **declare(strict_types=1)**: Mandatory in every file as the first statement.
- **Final Classes**: Use `final` on all classes unless explicitly designed for inheritance.
- **Readonly Classes**: Use `readonly` on immutable classes (Controllers, Actions, Payloads).
- **Property Hooks**: Use for simple data transformations at the property level.
- **Constructor Property Promotion**: Use whenever possible for cleaner code.

## 2. Laravel 13 Features

- **defer()**: Use to execute code after the response is sent to the user (non-critical side effects).
- **Context**: Use `Illuminate\Support\Facades\Context` to store global state per request (e.g., `trace_id`).
- **Concurrency**: Use `Concurrency::run()` for parallelizing I/O bound tasks.

## 3. Documentation (PHP Attributes)

We have moved away from DocBlocks for metadata and transitioned to **PHP Attributes**.

- **Scribe**: Use attributes from `Knuckles\Scribe\Attributes\*`.
- **Validation**: Use Form Request `rules()` method, but consider Attributes if native support is added in the future.

## 4. Observability (Trace ID)

Standardized request tracking:
1.  **Header**: API responses must include `X-Trace-ID`.
2.  **Context**: Store `trace_id` in Laravel Context at the beginning of the request (Middleware).
3.  **Logs**: Ensure every log entry includes the `trace_id` from Context.

## 5. Naming Conventions

- **Payloads**: Use the `Payload` suffix (not `DTO`).
- **Actions**: Use the `Action` suffix (e.g., `StoreUserAction`).
- **Controllers**: Use the `Controller` suffix and implement Single Action (`__invoke`).
- **Versions**: Version directories must be uppercase (V1, V2).

## 6. Testing Strategy

- **Pest**: The default testing framework.
- **Arch Testing**: Mandatory to maintain architectural integrity.
- **Factories**: Mandatory for all data states in testing.
