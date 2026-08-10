# ADR-0016: Accounting Rejected as a Module

- Status: Accepted
- Date: 2026-08-09

## Context

A new module was planned to prove the modular architecture. Accounting (chart of accounts, per-country tax) was a candidate.

## Decision

Reject Accounting as a module. It is domain-specific, large-scope, and cannot be generalized into a kit. Feature modules must be product/infrastructure modules with kit-wide reuse, not vertical domain products.

## Consequences

- Media Storage (ADR-0015) became the first feature module instead.
- Future module proposals are evaluated against kit-reuse value; domain-specific verticals are explicitly out of scope.
