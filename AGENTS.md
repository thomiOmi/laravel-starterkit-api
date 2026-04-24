<laravel-boost-guidelines>
# Laravel Boost: High-Performance Profile

## 1. Stack & Context
Expert in PHP 8.4 & Laravel 13.
Core: Sanctum v4, Pest v4, PHPUnit v12, Larastan v3, Pint v1.
MCP Tools: Use `laravel/boost` (v2) & `laravel/mcp` (v0) for all DB, Schema, and Doc searches.

## 2. Strategic Skills
Activate these contextually:
- `laravel-best-practices`: Eloquent (N+1), Auth, Caching, Architectural decisions.
- `pest-testing`: TDD, Feature/Unit tests, Pest 4 features.
- `laravel-permission`: Spatie roles, permissions, and policies.

## 3. Tooling Workflow (Strict)
1. **Research:** ALWAYS `search-docs` and `database-schema` before coding.
2. **Execution:** Use `php artisan make:*` for files. Use `php artisan tinker --execute '...'` (single quotes) for testing logic.
3. **Verification:** Write/Update Pest tests. Run `php artisan test --compact`.
4. **Cleanup:** Run `vendor/bin/pint --dirty --format agent` before finishing.

## 4. Technical Standards
- **PHP:** Constructor property promotion, explicit type hints, and return types.
- **Enums:** TitleCase keys (e.g., `ActiveStatus`).
- **Style:** PHPDoc blocks with array shapes for complex data; concise explanations.
- **Frontend:** Remind user to run `npm run dev` if UI doesn't update.

## 5. Constraints
- Concise replies only. Focus on implementation over explanation.
- No new base directories or dependency changes without approval.
- No custom verification scripts if Pest tests can handle it.

## 6. Modular Architecture Standards (CRITICAL)
- **Modularity:** All domain code MUST reside in `modules/`. Never use `app/Http/Controllers` for domain logic.
- **Generator:** ALWAYS prefer `php artisan make:module {Name}` for creating new modules. It ensures correct wiring of Controller, Filter, and Repository.
- **Data Flow:**
    - **Actions:** Use for single, atomic tasks (one `execute()` method).
    - **Services:** Use for complex orchestration or multi-step logic requiring `DB::transaction`.
    - **Filters:** Use `App\Filters\BaseFilter` pattern for all searching/sorting. Inject into Controller index methods.
- **Database:** Use **ULIDs** for primary keys and **Soft Deletes** for all domain models via `App\Traits\Models\HasDefaultBehavior`.
- **Communication:** Use **Events & Listeners** for cross-module interaction to maintain decoupling. Listeners should be registered in the reacting module's `ServiceProvider`.
- **Routing:** No versioning in routes (`v1/v2`). Use flat modular routes in `modules/*/Routes/api.php`.
</laravel-boost-guidelines>
