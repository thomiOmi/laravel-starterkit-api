# ADR-0010: Typed Config Access

- Status: Accepted
- Date: 2026-08-08

## Context

Bare `config()` returns `mixed`, which erodes PHPStan type coverage. Several call sites cast values manually.

## Decision

Use typed accessors everywhere: `config()->string()`, `->integer()`, `->boolean()`, `->array()`.

## Consequences

- PHPStan type coverage improves (no manual casts).
- Two documented exceptions remain: `apiroute.supported_versions` stays `config()` (mixed) because `config()->array()` returns `array<mixed>` which `suggest`/`implode` (array<string>) cannot consume, and `app.key` stays `config()->get()` (nullable by design, checked with `is_string`).

## Corrections

- 2026-11-23: The `apiroute.supported_versions` exception above no longer applies. Both module route providers now read the key with `config()->array('apiroute.supported_versions', ['V1'])` (`modules/IAM/app/Providers/RouteServiceProvider.php:42`, `modules/Media/app/Providers/RouteServiceProvider.php:68`), and `config/apiroute.php:26-28` defines it as an array of strings. The `app.key` exception remains valid. (Original text above is kept as written.)
- 2026-11-23: Typed getters throw when a value has the wrong type, so type-tolerant sites keep plain access by design: `session.secure` (`config/session.php:174` may be null and `ProductionSecurityCheck` reports failure via `=== true`), `media.allowed_extensions` and `media.disallowed_extensions` (callers use `is_array` fallbacks), `media.conversions_disk_name` (caller uses an `is_string` fallback to the upload disk), plus `config()->has(...)` in `EnsureFeatureIsActive` (an existence check, not a value read). Manual casts on keys guaranteed to be strings were replaced with `config()->string(...)` (PR #430). No automatic enforcement was added; that is a separate decision. (Original text above is kept as written.)
