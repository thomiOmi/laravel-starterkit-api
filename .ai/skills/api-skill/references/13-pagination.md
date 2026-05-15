# Pagination Standards

To keep the API performant, we prefer lean pagination methods that avoid unnecessary database overhead.

## 1. Simple Pagination

Always use `simplePaginate()` instead of `paginate()` for API endpoints.

- **Why?**: `paginate()` executes an expensive `SELECT COUNT(*)` query to determine the total number of pages. On large datasets, this significantly impacts performance.
- **Benefit**: `simplePaginate()` only provides "Next" and "Previous" links, which is usually sufficient for mobile apps and infinite scroll interfaces.

### Implementation:
```php
// In Repository or Action
return User::query()->simplePaginate(
    perPage: $perPage,
);
```

## 2. Default Configuration

- **Per Page**: Default to `15` or `20` items per page.
- **Customization**: Allow consumers to customize the page size via a `per_page` query parameter, but always set a maximum limit (e.g., 100) to prevent abuse.

## 3. Anti-Patterns

- ❌ Do not use `paginate()` on API list endpoints.
- ❌ Do not return the entire collection (`all()`) for resources that can grow large.
- ❌ Do not allow unrestricted `per_page` values from the client.
