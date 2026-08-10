# ADR-0009: Plain Abstract Readonly Controller Base

- Status: Accepted
- Date: 2026-08-08

## Context

The audit flagged the controller base class as unnecessary framework coupling. Laravel 13 docs state controllers do not need to extend a base class; `ControllerDispatcher` auto-falls back to direct method calls when `callAction` is absent.

## Decision

The base `app/Http/Controllers/Controller.php` is a plain `abstract readonly class Controller {}` (no `extends BaseController`). All controllers are `final readonly` and extend it. Middleware and validation stay explicit in routes and Form Requests ("Explicit over magic").

## Consequences

- All 34 IAM controllers and the module stub controllers consistently extend the plain base.
- PHP 8.4 forbids readonly classes extending non-readonly ones, so the base had to become readonly too; architecture tests `toBeFinal`/`toBeReadonly` remain green.
- Controllers carry no methods (arch test `not->toHavePublicMethodsBesides(['__construct', '__invoke'])` safe).
