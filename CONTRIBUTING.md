# Contributing to Laravel Starterkit API

Thanks for considering a contribution. This project is a starter kit, not a product: every change must keep the kit minimal, production-ready, and easy to fork.

## Development Environment

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

For CI-like preparation without an existing database:

```bash
composer setup:ci
```

## Code Style and Static Analysis

- Run `vendor/bin/pint --format agent` after editing PHP files.
- Run `composer types:check` (PHPStan level max). Fix the root cause of every error; never add `@phpstan-ignore` and never edit `phpstan.neon` to silence checks.
- Run `composer test:quality` before finishing: 100% code and type coverage is required.
- Do not change `tests/Architecture/ArchitectureTest.php` unless explicitly approved. Architecture tests are the source of truth for conventions.

## Testing

- This project uses Pest 5. Feature tests are preferred over unit tests.
- Every change must be programmatically tested. Add or update tests, then run the affected suite:

```bash
php artisan test --compact --filter=TestName
```

- Full quality gate before pushing:

```bash
composer ci:check
```

## Commit Conventions

This repository uses Conventional Commits:

- Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`, `ci`, `style`.
- Optional scope matching the touched area, e.g. `feat(iam)`, `docs(adr)`, `chore(github)`.
- Keep the message lowercase and concise, describing the change, not the intent.

A pre-commit hook runs `composer lint:staged` automatically.

## Working on Modules

- Create a module with `php artisan make:module`; see `docs/module-generator.md`.
- Modules are self-contained under `modules/{Module}/` and auto-discovered by `ModuleServiceProvider`. Do not register module routes, models, or migrations in the shared `app/` or `routes/` areas.
- Modules communicate through contracts, not direct imports. Keep the core (IAM) minimal and let feature modules extend it.
- Read `.ai/rules/` before creating or editing module code; conventions are settled there.

## Documentation

- `docs/` is the canonical documentation: PRD for what/why, ADRs for settled decisions, `docs/*.md` for how-to.
- New settled decision: write an ADR from `docs/adr/template.md` and add it to the index.
- `TASKS.md` is an untracked execution tracker; never commit it.

## Pull Requests

- Branch from `main` and open a pull request back to `main`.
- The `tests` workflow must pass on both PHP 8.4 and 8.5 (lint, static analysis, tests, coverage).
- Fill out the pull request template, including the type of change and checklist.
- Review feedback applies to the whole diff, not just the latest commit.

## Reporting Bugs and Requesting Features

- Use the issue templates: bug reports need version, reproduction steps, and environment; feature requests need the problem and proposed solution.
- Security issues must not be filed as public issues. See `SECURITY.md`.
