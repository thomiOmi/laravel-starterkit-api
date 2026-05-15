# Naming Conventions

Consistency in naming is crucial for a clean and maintainable codebase. This table defines the naming standards for all layers of the application.

| Layer | Convention | Example |
|---|---|---|
| **Controller** | `{Action}Controller` (within versioned namespace) | `StoreController`, `DestroyController` |
| **Action** | `{Action}{Resource}Action` | `StorePostAction`, `DeletePostAction` |
| **Payload** | `{Action}{Resource}Payload` | `StorePostPayload`, `UpdatePostPayload` |
| **Form Request** | `{Action}{Resource}Request` (or just `{Resource}Request` if shared) | `StorePostRequest`, `PostRequest` |
| **API Resource** | `{Resource}Resource` | `UserResource`, `PostResource` |
| **Job** | `{Action}{Resource}Job` | `DeletePostJob` |
| **Route Name** | `{module_name}.{action}` (relative to module prefix) | `users.store`, `users.index` |
| **Test File** | `{Action}Test.php` (within versioned namespace) | `StoreTest.php`, `IndexTest.php` |
| **Database Table** | Snake case, plural | `users`, `posts`, `user_roles` |
| **Model** | Pascal case, singular | `User`, `Post`, `UserRole` |

## Detailed Rules

### 1. Controllers
- Location: `modules/{Module}/Controllers/{Version}/{Action}Controller.php`
- Must be `final` and invokable.

### 2. Actions
- Location: `modules/{Module}/Actions/{Action}{Resource}Action.php`
- Must have an `execute` (or `handle`) method.

### 3. Payloads
- Location: `modules/{Module}/Payloads/{Action}{Resource}Payload.php`
- Must be `final readonly`.

### 4. Routes
- Names must be lowercase and dot-separated.
- `RouteServiceProvider` adds `api.{version}.{module}.` prefix automatically.

## Anti-Patterns

- ❌ Do not use generic names like `ProcessAction` or `DataController`.
- ❌ Do not use abbreviations (e.g., `UpdateUsrActn`).
- ❌ Do not mix plural and singular inappropriately.
- ❌ Do not use the `DTO` suffix (use `Payload` instead).
