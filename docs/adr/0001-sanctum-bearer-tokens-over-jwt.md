# ADR-0001: Sanctum Bearer Tokens over JWT

- Status: Accepted
- Date: 2026-08-03

## Context

The starterkit needed a token-based auth strategy. JWT (tymon/jwt-auth) is stateless and cannot support per-device session management.

## Decision

Use Laravel Sanctum bearer tokens. Each token maps to a device and can be listed, named, and revoked individually.

## Consequences

- Per-device revocation works out of the box (list/revoke/name endpoints).
- No JWT secret rotation or stateless-refresh complexity.
- Sessions are stored server-side, which requires a token store (database is sufficient).
