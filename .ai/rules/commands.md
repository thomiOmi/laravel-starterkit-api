---
paths:
  - 'modules/*/app/Console/Commands/**'
  - 'modules/*/app/Console/Commands/**'
  - 'app/Console/Commands/**'
---

# Commands

## Goal

Artisan commands in `app/Console/Commands/` (global) or `modules/{Module}/Console/Commands/` (module-specific).

## Rules

1. PHP 8 attributes: `#[Signature]`, `#[Description]`, `#[Help]`, `#[Usage]`
2. `handle(): int` with an exit code
3. Module commands are registered by the nWidart base `ModuleServiceProvider` while the module is active (no `withCommands` in `bootstrap/app.php`); global commands in `app/Console/Commands` are auto-discovered

## Forbidden

- No commands without a signature attribute
