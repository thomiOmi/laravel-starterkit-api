---
phase: 1
slug: quality-foundation
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
# audit-milestone §5.5 distinguishes NOT-VALIDATED (draft) from PARTIAL (validated + nyquist_compliant: false) (#2117)
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-08-03
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 5.0.2 (PHPUnit 13 engine) |
| **Config file** | phpunit.xml (testsuites: Architecture, Unit, Feature, Modules; sqlite :memory:, CACHE_STORE=array) |
| **Quick run command** | `php vendor/bin/pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` |
| **Full suite command** | `composer test` (lint + types + pest --parallel) |
| **Estimated runtime** | ~45 seconds (full suite), ~3 seconds (quick run) |

---

## Sampling Rate

- **After every task commit:** Run `php vendor/bin/pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` + `composer types:check`
- **After every plan wave:** Run `composer test:quality` (full gate incl. code coverage + type coverage)
- **Before `/gsd-verify-work`:** `composer ci:check` green (lint + types + coverage + rector + profanity)
- **Max feedback latency:** 45 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | API-02, API-03 | T-1-01 | Login per-email 429 contract + success-flow: 429 exposes X-RateLimit-Limit/Remaining/Retry-After/Reset (RED before fix); 200 carries Limit/Remaining + SuccessResponse shape (D-02/D-07) | feature | `pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01 | 1 | API-03 | T-1-02 | Per-IP limit (limit_per_ip=2) 429s 3rd distinct email; register/forgot-password/reset-password per-email + per-IP scenarios (D-01/D-03/D-04) | feature | `pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` | ❌ W0 | ⬜ pending |
| 01-01-03 | 01 | 1 | API-03 | T-1-01 | 429 render closure forwards `headers: $e->getHeaders()` (D-10); unit guard `assertHeader('Retry-After', '60')` in ExceptionHandlerTest | feature + unit | `pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` + `pest tests/Unit/Http/Exceptions/ExceptionHandlerTest.php --compact --no-tia` | ❌ W0 / ✅ existing | ⬜ pending |
| 01-02-01 | 02 | 2 | QLTY-01 | T-2-01 | phpstan.neon excludePaths removed (D-11/D-12) - module tests analyzed at max, 0 errors | static | `composer types:check` | ✅ existing | ⬜ pending |
| 01-02-02 | 02 | 2 | QLTY-02 | T-2-02 | 100% type coverage incl. new module test file (strict_types, typed closures) | static | `composer test:quality` | ❌ W0 | ⬜ pending |
| 01-02-03 | 02 | 2 | QLTY-01, QLTY-02 | — | Full CI gate green (lint + types + coverage + rector + profanity) - D-09 phase completion | static | `composer ci:check` | ✅ existing | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `modules/IAM/Tests/Feature/AuthRateLimitTest.php` — the phase deliverable itself (describe() per route, per-email/per-IP scenarios, one success-flow test)
- [ ] `bootstrap/app.php` 429 render closure fix (production change the tests verify — `headers: $e->getHeaders()`)
- [ ] `phpstan.neon` exclude removal (D-11)
- [ ] (optional) `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` 429 case gains `Retry-After` assertion

---

## Manual-Only Verifications

All phase behaviors have automated verification.

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 45s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
