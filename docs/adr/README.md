# Architecture Decision Records

This directory records architecture decisions (ADR) for the Laravel Starterkit API in the [Nygard format](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions). Each decision is one file, numbered in decision order.

## Index

| ID | Decision | Status | Date |
|----|----------|--------|------|
| [0001](0001-sanctum-bearer-tokens-over-jwt.md) | Sanctum Bearer Tokens over JWT | Accepted | 2026-08-03 |
| [0002](0002-ulid-only-primary-keys.md) | ULID-Only Primary Keys | Accepted | 2026-08-03 |
| [0003](0003-no-ip-user-agent-encryption.md) | No IP / User-Agent Encryption | Accepted | 2026-08-03 |
| [0004](0004-identity-contract.md) | Identity Contract for User Type-Hinting | Accepted | 2026-08-03 |
| [0005](0005-app-shared-vocabulary.md) | `app/` Is Shared Vocabulary and Contract | Accepted | 2026-08-07 |
| [0006](0006-module-policies-gate-policy.md) | Module Policies via `Gate::policy` | Accepted | 2026-08-08 |
| [0007](0007-iam-enums-in-app.md) | IAM Enums Stay in `app/Enums` | Accepted | 2026-08-07 |
| [0008](0008-single-route-discovery.md) | Single Route Discovery via RouteServiceProvider | Accepted | 2026-08-08 |
| [0009](0009-plain-abstract-readonly-controller.md) | Plain Abstract Readonly Controller Base | Accepted | 2026-08-08 |
| [0010](0010-typed-config-access.md) | Typed Config Access | Accepted | 2026-08-08 |
| [0011](0011-response-headers-parity.md) | Response Headers Parity Without a Shared Trait | Accepted | 2026-08-08 |
| [0012](0012-middleware-alias-in-provider.md) | Middleware Aliases in Module Service Provider | Accepted | 2026-08-08 |
| [0013](0013-native-enum-labels.md) | Native Enum Labels | Accepted | 2026-08-08 |
| [0014](0014-like-wildcard-full-scan.md) | LIKE Wildcard Full Scan Accepted | Accepted | 2026-08-09 |
| [0015](0015-media-storage-module.md) | Media Storage as First Feature Module | Accepted | 2026-08-09 |
| [0016](0016-accounting-rejected-module.md) | Accounting Rejected as a Module | Accepted | 2026-08-09 |
| [0017](0017-dead-code-removal.md) | Dead Code Branches Removed | Accepted | 2026-08-08 |
| [0018](0018-idempotency-opt-in-per-route.md) | Idempotency Keys Opt-In Per Route | Accepted | 2026-08-11 |
| [0019](0019-scramble-skipped.md) | Scramble OpenAPI Docs Skipped | Accepted (skip) | 2026-08-10 |
| [0020](0020-single-iam-seeder.md) | Single IAM Seeder | Accepted | 2026-08-07 |
| [0021](0021-social-auth-design.md) | Social Auth Design (Pivot, Stateless State, Email Binding) | Accepted | 2026-08-09 |
| [0022](0022-feature-flag-middleware.md) | Custom FeatureFlagMiddleware over Native Pennant | Accepted | 2026-08-10 |
| [0023](0023-tenancy-opt-in-organization-module.md) | Tenancy as an Opt-In Organization Module (Single Database) | Accepted | 2026-08-11 |
| [0024](0024-module-registry-allow-list.md) | Module Registry Allow-List Without Environment Toggle | Accepted | 2026-08-11 |
| [0025](0025-global-users-organization-membership.md) | Global Users With Organization Membership (Single-DB Data Model) | Accepted | 2026-08-11 |
| [0026](0026-rules-coverage-decisions.md) | Rules Coverage Decisions (G5) | Accepted | 2026-08-14 |
| [0027](0027-module-anatomy-migration-and-api-v2.md) | Module Anatomy Migration Timing and API V2 Versioning | Accepted | 2026-08-14 |

## Process

- A new ADR starts from `template.md`.
- Number sequentially; do not reuse or reorder numbers (Superseded decisions keep their number and point to the replacement).
- Status values: `Proposed`, `Accepted`, `Deprecated`, `Superseded by ADR-NNNN`.
- Record the decision when it is settled, with the reason ("why") — not just the outcome.
- Migrated from legacy decision tables on 2026-08-11; those working files are no longer the decision source of truth.