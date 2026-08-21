# ADR-0020: Single IAM Seeder, No Cross-Module Querying

- Status: Accepted
- Date: 2026-08-07

## Context

`UserSeeder` and `RoleSeeder` ran separately and `assignRolesToExistingUsers()` queried across modules, causing Spatie cache races in parallel CI and inconsistent seeding order.

## Decision

Merge into one seeder `IAMSeeder` in `Modules\IAM\Database\Seeders`, following the Spatie pattern: flush permission cache before, create permissions to roles to users, flush after. Factory states (`superAdmin()`, `admin()`, `user()`) assign roles in `afterCreating`.

## Consequences

- Deterministic seed order; `DatabaseSeeder` auto-discovers each active module's single seeder (`{Module}Seeder`, with `{Module}DatabaseSeeder` as fallback for `make:module` scaffolds).
- Parallel test runs no longer race on permission cache.
- Test callers (`BulkActionRequestTest`, `AuthRateLimitTest`, `loginAsRole` helpers) updated to the single seeder.
