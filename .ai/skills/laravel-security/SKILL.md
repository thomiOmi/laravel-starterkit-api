---
name: laravel-security
description: "Security and API response standards."
metadata:
  version: "1.2.0"
---

# Laravel Security

Security first approach with standardized responses.

## Key Rules
- Authorize EVERY write operation via Policy.
- Use `JsonDataResponse` for all success responses.
- See `references/api-standards.md` for JSON structures.

## Traceability
- Managed via Laravel Context.
- Reflected in `X-Trace-ID` header.
