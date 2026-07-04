# API Standards Handbook

This API is designed for professionalism and contract stability, specifically for integration with SPA and Mobile apps.

## 1. Error Handling (RFC 9457)

We use a global standard for error responses so clients can handle errors predictably.

### Error Workflow:
```mermaid
graph TD
    Req[Request] --> Val{Validation?}
    Val -- Fail --> RFC[ProblemResponse RFC 9457]
    Val -- Pass --> Proc[Process Action]
    Proc -- Exception --> RFC
    Proc -- Success --> Succ[SuccessResponse]
```

**Example Error Response (422):**
```json
{
  "type": "https://api.example.com/errors/validation-failed",
  "title": "Validation Error",
  "status": 422,
  "detail": "The provided data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

## 2. Idempotency (Idempotency-Key)

Critical for preventing duplicate data creation during unstable network connections (especially for Mobile).

### Idempotency Flow:
```mermaid
sequenceDiagram
    participant C as Mobile Client
    participant M as Idempotency Middleware
    participant A as Business Action

    C->>M: POST /orders (Header: Idempotency-Key: UUID-1)
    alt Key not in Cache
        M->>A: Process Order
        A-->>M: Order Created
        M->>M: Cache Result (Key: UUID-1)
        M-->>C: 201 Created
    else Key found in Cache
        M-->>C: Return Cached Response (201 Created)
    end
```

---

## 3. Streaming Response

Use this for large data exports without burdening the server's RAM.

**Example Case:** Exporting 100,000 transaction records to CSV.
- **Without Stream:** Server collects all data in memory -> RAM reaches limit -> Server crashes.
- **With Stream:** Server fetches 1 record -> sends it immediately to client -> fetches the next. RAM usage remains minimal.

---

## 4. Media & File Response

Always use **Signed URLs** for private/sensitive files.
- Links expire in 5-15 minutes.
- Prevents permanent exposure of private file links.

---

## 5. Rate Limiting Transparency

Every API response includes transparent headers:
- `X-RateLimit-Limit`: Maximum allowance.
- `X-RateLimit-Remaining`: Requests left.
- `X-RateLimit-Reset`: When allowance resets (Unix Timestamp).
