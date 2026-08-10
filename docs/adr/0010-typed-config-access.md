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
