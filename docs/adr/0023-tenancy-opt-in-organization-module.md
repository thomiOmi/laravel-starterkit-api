# ADR-0023: Tenancy as an Opt-In Organization Module (Single Database)

- Status: Accepted
- Date: 2026-08-11

## Context

The starterkit targets projects that fork or clone the repository, mostly single-tenant. Some will need multi-tenancy (a provider running multiple customer instances). The roadmap decision was: keep one public repository, self-hosted, with tenancy as an optional capability.

Research compared stancl/tenancy v3 with v4: v4's single-database tenancy is built on PostgreSQL Row-Level Security and requires PostgreSQL, while the kit is MySQL-first. v3 supports single-database tenancy with manual `tenant_id` scoping, which fits MySQL. v3.10 also declares support for illuminate ^13.

Making tenancy part of the core would force every installation to pay its cost: tenant/domain migrations, domain identification middleware, cache and filesystem bootstrappers, and query scoping.

## Decision

Install `stancl/tenancy:^3.10` as a dependency and wrap it in the `Organization` module, disabled by default (not listed in `config/modules.php`):

- Package auto-discovery is disabled via `extra.laravel.dont-discover` so the package is fully inert while the module is off.
- `OrganizationServiceProvider` extends `TenancyServiceProvider`, merging the module's `config/tenancy.php`, central migrations, and tenant route file only when the module is enabled.
- Single-database tenancy: `DatabaseTenancyBootstrapper` is omitted; tenant data stays in one database and is scoped by `tenant_id` columns. Cache and filesystem bootstrappers are kept.
- Tenant-scoped migrations live in `modules/Organization/Database/Migrations/tenant`, referenced by `tenancy.migration_parameters`.
- The tenant model remains the stancl default with a UUID string key; a domain-based organization model is a later phase.

## Consequences

- Tenancy is inert by default: no config, migrations, middleware, or routes are loaded (covered by tests).
- Enabling tenancy is a code decision: add `organization` to the module allow-list.
- Cross-cutting coupling is real: any module that later needs tenant-scoped data depends on the Organization module contract (`tenant_id`, scoping trait). This is accepted and documented here.
- Upgrading to tenancy v4 later requires a fresh config and provider (v4 relocates namespaces); the module boundary contains that change.
- While enabled, cache and filesystem bootstrappers change global cache and storage_path behavior.
