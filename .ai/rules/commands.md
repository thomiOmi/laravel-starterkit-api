---
paths:
  - 'modules/*/app/Console/Commands/**'
  - 'modules/*/app/Console/Commands/**'
  - 'app/Console/Commands/**'
---

# Commands

## Goal

Artisan commands in `app/Console/Commands/` (global) or `modules/{Module}/app/Console/Commands/` (module-specific).

## Rules

1. PHP 8 attributes: `#[Signature]`, `#[Description]`, `#[Help]`, `#[Usage]`
2. `handle(): int` with an exit code
3. Module commands are registered by adding the command class to the `$commands` array in the module's `{Module}ServiceProvider` (the nWidart base `ModuleServiceProvider` registers whatever is listed there; it does not auto-discover the folder); global commands in `app/Console/Commands` are auto-discovered

## Forbidden

- No commands without a signature attribute
