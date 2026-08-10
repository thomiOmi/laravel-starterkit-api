# ADR-0005: app/ Is Shared Vocabulary and Contract

- Status: Accepted
- Date: 2026-08-07

## Context

Contracts, Enums, and Concerns are used across layers (root Database/Middleware/HTTP/Providers and all modules). Architecture tests enforce `modules should be isolated`, so modules cannot import each other.

## Decision

`app/` is the shared vocabulary and contract layer. Dependency direction is always module to `app/`, never the reverse, and never module to module.

## Consequences

- Cross-cutting types (contracts, enums, concerns) have a single home.
- Module isolation is enforceable: modules only depend on `app/` and their own namespace.
- Architecture tests allow `app/` imports from any layer.
