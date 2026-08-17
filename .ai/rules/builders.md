---
paths:
  - 'modules/*/app/Builders/**'
  - 'app/Builders/**'
---

# Builders

## Goal

Custom Eloquent query builders registered via `#[UseEloquentBuilder]` on the model. `BaseQueryBuilder` is the only mechanism for filter, search, sort, include whitelists.

## Rules

1. `BaseQueryBuilder` is the only mechanism for filter, search, sort, include whitelists
2. Whitelist methods: `allowedSearch`, `allowedFilters`, `allowedSorts`, `allowedFields`, `allowedIncludes`
3. Models register the builder via attribute, not `newBuilder()`
4. Native Eloquent (`where`, `orderBy`, scopes) remains valid in actions/builders

## Forbidden

- No query string parsing in controllers
- No bypassing the whitelist with arbitrary parameters

## Example

```php
User::query()
    ->with(['roles'])
    ->allowedSearch()
    ->allowedFilters()
    ->allowedSorts()
    ->allowedFields()
    ->allowedIncludes()
    ->paginate();
```
