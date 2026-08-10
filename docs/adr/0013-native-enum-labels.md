# ADR-0013: Native Enum Labels

- Status: Accepted
- Date: 2026-08-08

## Context

Enums (e.g. `UserStatusEnum`) need human-readable labels in API responses and notifications. A third-party translation package was considered but adds a dependency for a simple need.

## Decision

Enums expose a `label()` method backed by `lang/en|id/enums.php`. Permission keys use underscores (`user_view`) because dot-notation `__()` breaks keys containing dots (`user.view`). `UserResource` exposes `status_label`.

## Consequences

- No third-party package needed; same pattern as `blockedMessageKey()`.
- Labels are localized (en + id) and co-located in the language files.
- Key naming quirk (underscore) is documented for future permission keys.
