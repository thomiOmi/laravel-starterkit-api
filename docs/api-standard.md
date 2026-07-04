# API Standards Handbook

Professional, production-ready API standards used in this starter kit.

## 1. Error Handling (RFC 9457)

We don't just return `{"error": "message"}`. We follow the **Problem Details** standard.

### The Story: User Validation Fails
When a user registers with an existing email, the API responds:

```json
{
  "type": "https://api.example.com/errors/validation-failed",
  "title": "Validation Error",
  "status": 422,
  "detail": "The email provided is already registered.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

## 2. Idempotency

Prevent double-processing of sensitive requests (like payments).

### How to use it:
Send a `Idempotency-Key` header with a unique UUID.
- **First Request:** Server processes and caches the result.
- **Second Request (with same key):** Server immediately returns the cached result without re-processing.

## 3. Rate Limiting Transparency

We help frontend developers manage limits by sending these headers:
- `X-RateLimit-Limit`: Maximum requests.
- `X-RateLimit-Remaining`: Requests left.
- `X-RateLimit-Reset`: When the limit resets.

## 4. Response Consistency

Every successful response follows this structure:

```json
{
  "status": "success",
  "message": "Resource created successfully",
  "data": { ... }
}
```

## 5. Stream Responses

For large exports, we use `StreamedResponse`. This allows the server to send data line-by-line, keeping memory usage near zero regardless of file size.
