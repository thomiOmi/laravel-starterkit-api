# ADR-0018: Idempotency Keys Opt-In Per Route

- Status: Accepted (revised 2026-08-11)
- Date: 2026-08-11

## Context

The first design applied the idempotency middleware to the whole `api` group. Review showed: (1) Sanctum's default guard resolves the token lazily, so a global application adds no extra security; (2) it conflicts with the "Explicit over magic" philosophy; (3) most routes have no duplicate-submission risk worth protecting.

## Decision

Apply idempotency opt-in per route. Only `POST v1.iam.register` (auth.register) carries it, with middleware order `['feature-flag:iam.self-registration', 'throttle:auth', 'idempotency']` (flag outermost, throttle second, idempotency before the controller).

## Consequences

- The middleware stays available as a documented toolkit item; new routes opt in deliberately.
- Register is protected against double-submission (replay returns stored 201, differing body returns 409).
- `Idempotency-Key` must be a UUID v4 (422 otherwise); responses carry `Idempotency-Replayed: true` on replays.
