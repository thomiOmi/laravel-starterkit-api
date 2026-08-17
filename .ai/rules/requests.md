---
paths:
  - 'modules/*/app/Http/Requests/**'
  - 'modules/*/app/Http/Requests/**'
  - 'app/Http/Requests/**'
---

# Requests

## Goal

One FormRequest per endpoint in `modules/{Module}/app/Http/Requests/V1/`. Cross-module requests (pagination, bulk action) live in `app/Http/Requests/` (shared).

## Rules

1. One FormRequest per endpoint/action; the only exceptions are shared requests in `app/Http/Requests/` (`PaginationRequest`, `BulkActionRequest`) used across endpoints
2. Validation in `rules()`; authorization via `authorize()` or policy/permission
3. No inline validation in controllers
4. List endpoints must type-hint a `{Resource}ListRequest` in the module that extends `App\Http\Requests\PaginationRequest` (not PaginationRequest directly): the place for `authorize()` permission and extra rules for filter/sort/search; empty subclasses are allowed when only pagination is needed (existing pattern: `UserListRequest`, `RoleListRequest`, `PermissionListRequest`, `DeviceListRequest` in `modules/IAM/app/Http/Requests/V1/`)
5. Request naming follows the controller: `{Resource}ListRequest` for `{Resource}ListController`

## Forbidden

- No long validation arrays in controllers
- No Request calling models directly
- No list controller type-hinting `PaginationRequest` directly from app
