# Technology Stack

**Project:** Laravel Starterkit API
**Researched:** 2026-08-03

## Recommended Stack

### Core Framework
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel (framework) | 13.x (current: 13.18) | Application framework | Zero breaking changes from 12; native PHP attributes across 15+ locations; queue infra maturity (DebounceFor, Bus::bulk); JSON health route support since 13.6 |
| PHP | 8.4 | Language runtime | Laravel 13 requires 8.3 minimum; 8.4 current stable with typed class constants, readonly improvements |
| MySQL | 8.x | Primary database | Project mandate; proven relational store |

### Authentication & Authorization
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Sanctum | 4.x | Token-based auth | Per-device token management (create/list/revoke/name) — the settled decision over JWT; maps naturally to device-per-token model |
| Spatie laravel-permission | 6.x | Roles & permissions | Mature, guard-aware, cacheable; supports `roles:id,name,guard_name` sparse eager-loading |
| Laravel Socialite | 5.x | Social login | Official OAuth client; `Socialite::fake()` test support |

### Feature Flags
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Pennant | 1.x | Feature flags | Official; class-based features (app/Features/) with `Feature::fake()` testing; scope to users/teams; gradual rollouts |

### API Layer
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Eloquent API Resources | built-in | Response contract | JsonResource guarantees stable output shape; `#[UseResource]`/`#[UseResourceCollection]` attributes in Laravel 13 |
| Form Requests | built-in | HTTP validation | Explicit validation per endpoint; Laravel 13 adds `#[ErrorBag]`, `#[RedirectToRoute]`, `#[StopOnFirstFailure]` attributes |
| Scramble | 0.12+ | API documentation | OpenAPI generation from PHP attributes/reflection; zero docblock noise; alternative: Scribe (used by JustSteveKing/kit) — Scramble already decided in project roadmap |

### Infrastructure & Observability
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Redis | 7.x | Cache/queues/sessions | High-performance shared cache; production cache store for idempotency; project mandate |
| Laravel Pulse | 1.x | Production monitoring | Slow query/route tracking, alert thresholds — lightweight first-party observability |
| Laravel Telescope | 5.x | Dev debugging (dev only) | Request profiling, exceptions, mail previews — dev environment only, never production |
| Docker / FrankenPHP | latest | Deployment | Octane-style performance with Swoole/Worker mode; single binary; multi-stage build |

### Code Quality
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Pint | 1.x | Code style | Official formatter; PSR-12 + Laravel preset |
| PHPStan (Larastan) | 3.x | Static analysis | level: max; with phpstan-deprecation-rules; memory limit 512M |
| Pest | 5.x | Testing | Feature/unit tests with describe() blocks, datasets, custom expectations; --agent one-shot verification |
| Pest plugins | 5.x | Advanced testing | stress, mutation, snapshot, profanity — evaluate per project roadmap |
| Rector | 2.x | Automated refactoring | Upgrades and code modernization in CI |

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Auth | Sanctum tokens | tymon/jwt-auth | JWT stateless; no per-device revocation — settled decision (2026-06-29) |
| API docs | Scramble | Scribe | Scramble uses reflection/attributes with zero config noise; already in project roadmap |
| Architecture | Laravel-first | hexagon/ports-and-adapters | Overengineering for starterkit scope; project philosophy explicitly rejects unused abstractions |
| Repositories | None (Eloquent) | Repository pattern | Eloquent ORM IS the repository; project rule: use Model Scopes/Services instead |
| Error format | RFC 9457 (ProblemResponse) | JSON:API errors | RFC 9457 simpler, matches project contract `{status, title?, detail?, data, meta?}` |
| Job infra | Built-in (DebounceFor, Bus::bulk) | horizon-side packages | Laravel 13.6+/13.13+ ships these natively — no package needed |

## Installation

```bash
# Core stack already installed (composer.json): laravel/framework ^13, sanctum, spatie/laravel-permission, socialite, pennant
# Additions to evaluate in roadmap phases:
composer require dedoc/scramble               # API docs
composer require laravel/pulse                # production monitoring
composer require laravel/telescope --dev      # dev debugging
composer require larastan/larastan --dev      # static analysis (installed)
composer require phpstan/phpstan-deprecation-rules --dev
composer require mrpunyapal/peststan --dev    # PHPStan for test files (evaluate)
```

## Sources

- Laravel 13 changelog (laravel.com/docs/changelog) — Bus::bulk() June 2026, DebounceFor 13.6.0
- Laravel News: 13.6.0 debounceable jobs (2026-04-22), 13.13.0 Bus::bulk (2026-06-03)
- laraveldaily: PHP Attributes in Laravel 13 — 36 new attributes (2026-03-18)
- GitHub: JustSteveKing/kit — opinionated API starterkit patterns (2026-02-23)
- GitHub: square1-io/laravel-idempotency — idempotency middleware best practices
