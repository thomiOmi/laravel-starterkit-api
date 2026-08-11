# ADR-0025: Global Users With Organization Membership (Single-DB Data Model)

- Status: Accepted
- Date: 2026-08-11

## Context

With single-database tenancy (ADR-0023), we must decide where identity lives. Two options were considered:

1. Global `users` table - one identity across all organizations, membership expressed as a relation.
2. Per-tenant users (a `users` table inside each tenant database) - requires a multi-database strategy, which v3 on MySQL does not support well.

A provider running multiple customer organizations wants one login and one identity for a person who belongs to several organizations. Per-tenant users would duplicate accounts and complicate auth.

## Decision

- `users` stays a single global table managed by the core IAM module; authentication is organization-agnostic.
- Organizations (the tenants) are managed by the Organization module; a domain model for them is a later phase. For now the stancl tenancy tables (`tenants`, `domains`) hold the organization data.
- Membership is expressed with a pivot relation (user to organization) resolved through the tenancy tables; role data per organization lives in the Organization module and follows the IAM role/permission contract when introduced.
- Tenant-scoped feature data (in later phases) carries `tenant_id` columns; scoping is implemented by the Organization module, never inline in feature modules.

## Consequences

- One account, one password, one session per user across all organizations they belong to - a clean story for multi-org providers.
- Feature modules must not assume a global identity: they receive tenant context through the Organization contract.
- Global super-admin/operator roles remain a core IAM concern; organization-level roles are module-scoped. The boundary is explicit and must be maintained.
- The stancl tenant id is a UUID string (package default), a deliberate deviation from ADR-0002 (ULID primary keys); it is contained inside the module boundary.
- Migrating to multi-database tenancy later is a major data model change; this ADR records that this choice is a deliberate first version, not a hidden default.
