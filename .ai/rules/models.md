---
paths:
  - 'modules/*/app/Models/**'
  - 'app/Models/**'
---

# Models

## Goal

Module-owned Eloquent models in `modules/{Module}/app/Models/`. Data access belongs to the module. `app/Models` holds only framework overrides that must extend vendor classes (e.g. `Sanctum/PersonalAccessToken`); domain models never live in `app/Models`.

## Rules

1. ULID primary keys via `HasDefaultBehavior` (HasUlids + serializeDate `Y-m-d H:i:s`)
2. Attributes via PHP 8 attributes: `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, `#[UseEloquentBuilder]` (never `$fillable`/`$hidden` properties)
3. Related model registrations via attributes: `#[UsePolicy]` (policy), `#[ObservedBy]` (observer), `#[ScopedBy]` (global scope)
4. `#[Table]`, `#[UseResource]`, `#[UseResourceCollection]` only for convention deviations (non-standard table names, pivots, non-standard resource naming)
5. Cast enum columns to enum classes (`'status' => StatusEnum::class`)
6. `declare(strict_types=1)` in every file
7. Factories are required for models used in tests
8. App-layer (tests/) may import module classes directly; `tests/Helpers.php` is a convenience seam for shared helpers, not a hard boundary
9. Soft deletes use the `Illuminate\Database\Eloquent\SoftDeletes` trait (the `#[UseSoftDeletes]` attribute does not exist in Laravel 13); `withTrashed`/`onlyTrashed` queries only in actions/builders

## Forbidden

- No UUID primary keys
- No `$fillable`/`$hidden` properties
- No cross-module models

## Example

```php
use HasDefaultBehavior;

#[Fillable]
#[Hidden]
#[UseFactory(UserFactory::class)]
#[UseEloquentBuilder(UserBuilder::class)]
#[UsePolicy(UserPolicy::class)]
class User extends Model
{
    protected $casts = [
        'status' => StatusEnum::class,
    ];
}
```
