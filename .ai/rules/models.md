---
paths:
  - 'modules/*/Models/**'
---

# Models

## Goal

Module-owned Eloquent models in `modules/{Module}/Models/`. Data access belongs to the module.

## Rules

1. ULID primary keys via `HasDefaultBehavior` (HasUlids + serializeDate `Y-m-d H:i:s`)
2. Attributes via PHP 8 attributes: `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, `#[UseEloquentBuilder]` (never `$fillable`/`$hidden` properties)
3. Related model registrations via attributes: `#[UsePolicy]` (policy), `#[ObservedBy]` (observer), `#[ScopedBy]` (global scope)
4. `#[Table]`, `#[UseResource]`, `#[UseResourceCollection]` only for convention deviations (non-standard table names, pivots, non-standard resource naming)
5. Cast enum columns to enum classes (`'status' => StatusEnum::class`)
6. `declare(strict_types=1)` in every file
7. Every model must have a factory
8. App-layer (tests/) accesses module models only through the `tests/Helpers.php` seam, not direct imports
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
final class User extends Model
{
    protected $casts = [
        'status' => StatusEnum::class,
    ];
}
```
