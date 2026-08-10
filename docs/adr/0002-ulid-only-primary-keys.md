# ADR-0002: ULID-Only Primary Keys

- Status: Accepted
- Date: 2026-08-03

## Context

The kit considered UUID, integer, and a configurable ID strategy. Each extra strategy adds dead code paths and cognitive load for kit consumers.

## Decision

Use ULIDs as the only primary key format across all models and tables.

## Consequences

- Dead code paths (configurable ID strategy) are removed (YAGNI).
- ULIDs are sortable and opaque; routes use `whereUlid` constraints.
- Consumers cannot switch to integer or UUID without a migration.
