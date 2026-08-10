# ADR-0007: IAM Enums Stay in app/Enums

- Status: Accepted
- Date: 2026-08-07

## Context

`UserStatusEnum` is used by root migrations; `PermissionEnum` is used by `app/Http/Requests/BulkActionRequest.php`. Both sit outside the architecture-test allowlist, so moving them into the IAM module would break `modules should be isolated`.

## Decision

IAM enums remain in `app/Enums`. The rule is: files outside the allowlist must not import module code.

## Consequences

- One principle covers all cases: root files never import from modules.
- Enum usage is not a module-inconsistency; it is the expected direction of dependency.
- New feature modules should keep module-private enums inside the module; only shared/root-referenced enums belong in `app/Enums`.
