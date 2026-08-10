# ADR-0014: LIKE Wildcard Full Scan Accepted

- Status: Accepted
- Date: 2026-08-09

## Context

`BaseFilter` uses `%value%` (leading wildcard), which cannot use a B-tree index. FULLTEXT indexing was considered as an alternative.

## Decision

Accept the full scan. The starterkit dataset is small, input truncation guards already exist, and FULLTEXT is MySQL-specific (incompatible with the SQLite test environment) — it would be overengineering for a kit.

## Consequences

- Filter queries stay portable across MySQL (prod) and SQLite (tests).
- Large-dataset consumers must replace `like` filtering or add their own search index (Scout) — acceptable for a starterkit.
- Decision recorded at Media module filter design (P2-F) and remains the standing rule.
