---
name: laravel-specialist
description: Build and configure Laravel 13+ applications using Modular Monolith patterns. Includes Eloquent models with Property Hooks, Sanctum auth, modular routing, and Pest testing. Use when creating models, migrations, or features within the Modules/ directory.
license: MIT
metadata:
  version: "2.0.0"
  triggers: Laravel 13, PHP 8.4, Property Hooks, Modules, Modular Architecture, Eloquent, Pest
---

# Laravel Specialist (L13 & PHP 8.4)

Senior Laravel specialist expert in Laravel 13, PHP 8.4, and Modular Monolith architecture.

## Core Workflow

1. **Analyse Requirements** — Identify the target Module (`Modules/{Name}`).
2. **Database Design** — Create migrations. **Verification:** Run `php artisan migrate` and use `database-schema` to verify table structure.
3. **Model Implementation** — Use `final` classes, `HasDefaultBehavior` trait, and **Property Hooks** for derived logic.
4. **Feature Development** — Implement Actions, Payloads (DTO), and API Resources.
5. **Testing** — Write Pest feature tests. **Verification:** Run `php artisan test --compact`.
6. **Code Quality** — Run `./vendor/bin/pint --format agent` and `phpstan`.

## Reference Guide

| Topic | Reference | Load When |
|-------|-----------|-----------|
| Architecture | `references/architecture.md` | Module isolation, Contracts vs Events |
| Eloquent | `references/eloquent.md` | Property Hooks, Scopes, Relationships |
| Testing | `references/testing.md` | Pest, Arch testing, Mocking |

## Constraints

### MUST DO
- Use **PHP 8.4 Property Hooks** instead of Laravel `Attribute` classes.
- Use `final` keyword for all new classes.
- Keep Controllers thin; move logic to **Actions**.
- Action must receive a **Payload (DTO)** object.
- **Isolate Modules:** Never import a Model from another module. Use Contracts in `app/Contracts`.
- Use `database-schema` MCP tool after every migration change.
- Use `search-docs` for any Laravel 13 specific feature queries.

### MUST NOT DO
- Do NOT use the Repository Pattern.
- Do NOT use `$guarded = []`. Use `#[Fillable]` or `$fillable`.
- Do NOT import internal module classes from other modules.

## Code Templates

### Model with Property Hooks
```php
final class Post extends Model
{
    use HasDefaultBehavior;

    public string $slug {
        set => str($value)->slug()->toString();
    }

    public string $excerpt {
        get => str($this->content)->limit(100);
    }
}
```

### Action with Payload
```php
final readonly class CreatePostAction
{
    public function handle(CreatePostPayload $payload): Post
    {
        return Post::create($payload->toArray());
    }
}
```

## Verification Loop
After any structural change, you MUST:
1. Run `php artisan migrate` (if database change).
2. Use `database-schema` to confirm the DB state.
3. Run `php artisan route:list --path=api` to confirm new endpoints.
