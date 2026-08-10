# ADR-0011: Response Headers Parity Without a Shared Trait

- Status: Accepted
- Date: 2026-08-08

## Context

`SuccessResponse` and `ProblemResponse` handled headers differently (nulls, ints, arrays). Extracting a shared trait was considered; it would couple the two response envelopes.

## Decision

`SuccessResponse` mirrors `ProblemResponse` header handling with a private `normalizeHeaders()` copy (null skipped, int/array stringified). The duplication is accepted; no shared trait.

## Consequences

- Consistent header output across both envelopes.
- Small duplication is deliberate and documented — extracted trait would be overengineering for two consumers.
- New response types copy the same private pattern.
- The `@template T` generic on `SuccessResponse` is retained: 34 IAM controllers type-hint `SuccessResponse<T>` in their `@return`; removing it breaks generics checks everywhere (deviation from the audit plan, recorded 2026-08-08).
