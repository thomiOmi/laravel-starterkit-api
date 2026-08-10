# ADR-0017: Dead Code Branches Removed, No Speculative Paths

- Status: Accepted
- Date: 2026-08-08

## Context

`BulkActionRequest` contained a branch handling `.role.` + `restore` actions. Only bulk delete exists for roles in `modules/IAM/routes/V1.php`; there is no `bulk.restore` route. PHPStan cannot detect this class of dead code, so it silently accumulates.

## Decision

Remove dead branches and do not keep speculative paths ("add it later = 1 line + test" mindset).

## Consequences

- `BulkActionRequest` no longer references a non-existent capability.
- No behavioral change; `BulkActionRequestTest` (9 tests) stays green.
- New bulk actions must be added together with their route and tests, not pre-wired.
