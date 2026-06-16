# API Standards

## Base URL

All endpoints: `/api/v1/{resource}/...`

## Authentication

```
Authorization: Bearer {token}
```

## Response Format

### Success (single resource)

```json
{
    "status": 200,
    "message": "User retrieved successfully",
    "data": { ... }
}
```

### Success (paginated list)

```json
{
    "status": 200,
    "message": "Users retrieved successfully",
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 72
    }
}
```

### Error (RFC 9457 ProblemResponse)

```json
{
    "type": "http://localhost/validation-error",
    "title": "Validation Error",
    "status": 422,
    "message": "Validation Error",
    "detail": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

Error types by status:

| Status | Type | Title |
|--------|------|-------|
| 400 | `/validation-error` | Validation Error |
| 401 | `/unauthenticated` | Unauthenticated |
| 403 | `/forbidden` | Forbidden |
| 404 | `/not-found` | Not Found |
| 422 | `/validation-error` | Validation Error |
| 429 | `/rate-limited` | Too Many Requests |

## Date Format

All datetime fields: `Y-m-d H:i:s` (e.g. `2026-04-23 15:19:09`)

## Locale

Set via `Accept-Language` header. Supported: `en` (default), `id`.

## Versioning

URL-prefixed (`/api/v1/`). Supported versions defined in `config/apiroute.php`. Unsupported versions return 404.
