# ADR-0019: Scramble OpenAPI Docs Skipped

- Status: Accepted (skipped)
- Date: 2026-08-10

## Context

API-05 planned Scramble-based OpenAPI documentation. `dedoc/scramble` is installed with `config/scramble.php`, but the starterkit response format is custom RFC 9457-style (SuccessResponse/ProblemResponse), not OpenAPI-shaped; OpenAPI contract tests were already rejected.

## Decision

Skip Scramble documentation for now. The package and config remain installed so the feature can be resumed without re-installation.

## Consequences

- No generated OpenAPI docs shipped with the kit; API surface is documented via `docs/api-standard.md`, route conventions, and ADRs.
- `config/scramble.php` stays as-is; resume by enabling the route and validating generated schemas.
