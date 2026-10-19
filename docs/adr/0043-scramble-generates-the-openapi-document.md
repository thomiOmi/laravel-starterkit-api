# ADR-0043: Scramble Generates the OpenAPI Document

- Status: Accepted
- Date: 2026-10-19

## Context

ADR-0019 decided to "Skip Scramble documentation for now", keeping `dedoc/scramble` and `config/scramble.php` installed for a possible resume. Since then the package has remained a production dependency (`dedoc/scramble` `^0.13.30` in the `require` section of `composer.json`) and Scramble is live: it registers `GET docs/api` (the UI) and `GET docs/api.json` (the document), and `php artisan scramble:export` produces a JSON document marked `openapi: 3.1.0` with 32 paths and 43 operations.

The docs routes run `Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess` (listed in the `middleware` key of `config/scramble.php`). The middleware allows requests from the local environment; outside local it allows only when the `viewApiDocs` gate grants access and otherwise aborts with 403. The kit does not define a `viewApiDocs` gate, so the generated docs are reachable in the local environment only.

## Decision

Scramble is the supported OpenAPI generator for the kit; the generated document is an accepted documentation surface instead of a skipped one. This supersedes ADR-0019.

- `config/scramble.php` remains the single source of truth for generation settings.
- Docs access keeps the default middleware behavior: local environment only; a deployment that wants the docs outside local must define a `viewApiDocs` gate.

## Consequences

- The kit maintains two documentation surfaces that must be kept consistent: the generated OpenAPI document and the hand-written API contracts (`docs/api-standard.md` and the module READMEs).
- The generated document has not been validated against the OpenAPI meta-schema; that validation remains a known gap.
- Enabling docs outside local is a deployment decision: define a `viewApiDocs` gate and the existing middleware opens access without other changes.
