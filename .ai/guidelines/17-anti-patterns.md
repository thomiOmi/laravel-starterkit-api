# Anti-Patterns Reference

The following patterns are explicitly prohibited in this project. If you find yourself reaching for one of these, stop and apply the correct approach instead.

| Anti-Pattern | Why? | What to do instead |
|---|---|---|
| **Multi-method / Resourceful Controllers** | Violates SRP, makes logic harder to locate, and bloats files. | Use **Single-Action Invokable Controllers** (`__invoke`). |
| **Business logic in Models** | Makes models harder to test and violates Single Responsibility. | Use **Action Classes** for business logic. |
| **Returning raw Models/Arrays** | Leaks database schema and lacks a transformation layer. | Always use **API Resources**. |
| **Manual `app()` / `resolve()` calls** | Hides dependencies and makes unit testing difficult. | Use **Constructor Injection**. |
| **`paginate()` on API endpoints** | Runs expensive `COUNT(*)` queries that impact performance. | Use **`simplePaginate()`**. |
| **`DataTableDTO`** | Over-engineered and redundant when using filters. | Use **`BaseFilter`** and standard pagination helpers. |
| **Unthrottled routes** | Exposes endpoints to brute force and resource abuse. | Always include **`throttle:api`**. |
| **HTML error responses** | Breaks API clients that expect JSON structures. | Use **`ForceJsonResponse`** and RFC 9457 Problem Details. |
| **Skipping `strict_types=1`** | Allows silent type coercion bugs. | Required on **every** file. |
| **Authorization checks in Actions** | Actions should receive data that is already authorized. | Perform authorization in **Form Requests**. |
| **`DB::transaction()` Facade** | Hides the database dependency. | Inject **`DatabaseManager`** and use `$database->transaction()`. |
| **Spatie Query Builder** | We use a custom, more tailored Filter system. | Extend **`BaseFilter`** for filtering and sorting. |
| **Manual OpenAPI files** | Becomes outdated quickly and requires double maintenance. | Use **Scramble Attributes** (`#[QueryParameter]`, etc.). |
| **Untagged Controllers** | Makes API documentation disorganized and hard to navigate. | Use the **`@tags`** PHPDoc tag in Controllers. |
| **Generic Repository logic** | Over-engineered for simple CRUD. | Use **Eloquent directly inside Actions**. |

## Why avoid these?

Avoiding these anti-patterns ensures that the codebase remains:
1. **Maintainable**: Easy to find and fix bugs.
2. **Predictable**: Consistent patterns throughout the project.
3. **AI-Friendly**: Clear boundaries help AI agents generate more accurate code.
4. **Performant**: Avoiding expensive operations like global pagination.
5. **Documented**: Ensures the API documentation is always in sync with the code.
