# Eloquent Model Standards

This starter kit is designed to be flexible regarding Model identifiers and follows strict Eloquent practices to catch bugs early.

## 1. Flexible Identifiers

We do not force a single identifier type. You may choose the most appropriate one for your resource:

- **Auto-increment Integer**: Standard for simple internal resources.
- **UUID**: Good for distributed systems or when you want non-guessable IDs.
- **ULID**: Recommended for lexicographically sortable, non-guessable IDs.

### Guidelines:
- Use `HasUuids` or `HasUlids` traits from Laravel when using non-integer IDs.
- Ensure the migration matches the chosen type (e.g., `$table->ulid('id')->primary()`).
- Always use the chosen identifier consistently throughout the resource's lifecycle.

## 2. Model Strictness

To prevent common bugs during development, we enable strict mode for models.

### Protections Enabled:
1. **Prevent Lazy Loading**: Throws an exception if a relationship is accessed but not eager-loaded (prevents N+1).
2. **Prevent Silently Discarding Attributes**: Throws if you try to fill a field that isn't in the `$fillable` (or equivalent) array.
3. **Prevent Accessing Missing Attributes**: Throws if you try to read a field that wasn't selected in the query.

*Note: These are enabled in `AppServiceProvider::boot()` for non-production environments.*

## 3. Mass Assignment

We use PHP Attributes for modern mass assignment management:

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable { ... }
```

## 4. Anti-Patterns

- ❌ Do not use auto-increment integers for sensitive public-facing resources where enumeration is a risk.
- ❌ Do not ignore lazy-loading exceptions; fix them by eager-loading with `with()`.
- ❌ Do not put business logic in Models (use Actions instead).
- ❌ Do not use `$guarded = []` unless you fully understand the security implications.
