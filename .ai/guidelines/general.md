# Laravel 13 Standard Coding Guidelines

## Core Tech Stack
- **Framework:** Laravel 13 (Modular Monolith)
- **Runtime:** PHP 8.4+
- **Database:** MySQL 8.0+
- **Testing:** Pest 4.0+
- **Static Analysis:** PHPStan (Larastan 3.0)
- **Formatting:** Laravel Pint

## Architectural Constraints (The Law)
1. **Module Isolation:** `Modules/A` MUST NOT import anything from `Modules/B/Models`. Use `app/Contracts`.
2. **Thin Controllers:** Controllers only handle HTTP. Logic goes to **Actions**.
3. **Action Payloads:** Actions MUST receive a **Payload (DTO)** object, not a `Request` or `array`.
4. **Final Classes:** All new classes MUST be `final` unless explicitly designed for inheritance.
5. **Property Hooks:** Use PHP 8.4 **Property Hooks** for derived logic in Models and Payloads.

## API Standards
- **Errors:** All error responses MUST follow **RFC 9457 (ProblemResponse)**.
- **Consistency:** Use `SuccessResponse` for 200/201 responses.
- **Resources:** Always use `JsonResource` for data transformation.
- **Headers:** Support `Idempotency-Key` and provide `X-RateLimit` headers.

## Development Workflow (Verification Loop)
1. **Before Code:** Use `database-schema` to understand existing tables.
2. **During Code:** Use `search-docs` for L13/PHP 8.4 syntax help.
3. **After Code:**
   - Run `./vendor/bin/pint --format agent`
   - Run `./vendor/bin/phpstan analyse`
   - Run `php artisan test --compact`
   - Check `database-schema` if migrations were added.

## File Ownership
- **AGENTS.md:** Managed by `php artisan boost:install`. **DO NOT EDIT.**
- **.ai/guidelines/**: Edit these source files to update AGENTS.md rules.
- **.ai/skills/**: Edit these to update agent domain expertise.
