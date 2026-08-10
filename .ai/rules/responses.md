---
paths:
  - 'app/Http/Responses/**'
---

# Responses

## SuccessResponse / ProblemResponse contract (RFC 9457)
All API responses use the shared envelopes in app/Http/Responses: SuccessResponse renders {status, title?, detail?, data, meta?} and ProblemResponse renders RFC 9457 problem details {type, title, status, detail, timestamp}. Never add a 'success' boolean. Errors always go through ProblemResponse (or the exception handler) with Content-Type application/problem+json; 4xx/5xx map to problem details automatically via the handler. Error types come from config/errors.php typeKey.
