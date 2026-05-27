# API Response Standards

## Successful Responses
Use `JsonDataResponse`. Boolean `success` is prohibited.
```json
{
    "status": 200,
    "message": "Success message",
    "data": { ... }
}
```

## Error Responses
Use `ProblemResponse` (RFC 9457).
```json
{
    "status": 403,
    "message": "Forbidden",
    "detail": "You do not have permission to perform this action.",
    "instance": "/api/v1/resource"
}
```

## Observability
Include `trace_id` in logs and headers. Use Laravel Context.
