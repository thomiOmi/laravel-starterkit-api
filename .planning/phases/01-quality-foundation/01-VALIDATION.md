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
| 01-01-01 | 01 | 1 | API-03 | T-1-01 / — | 429 responses expose Retry-After/X-RateLimit-Reset via `headers: $e->getHeaders()` | feature | `pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01 | 1 | API-02, API-03 | T-1-01 / — | 200 login success carries X-RateLimit-Limit/Remaining + SuccessResponse shape | feature | `pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` | ❌ W0 | ⬜ pending |
| 01-01-03 | 01 | 1 | API-03 | T-1-02 / — | Per-email limit (limit_per_email=2) 429s 3rd attempt from same email | feature | `pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` | ❌ W0 | ⬜ pending |
| 01-01-04 | 01 | 1 | API-03 | T-1-01 / — | Per-IP limit (limit_per_ip=2) 429s 3rd distinct email from same IP | feature | `pest modules/IAM/Tests/Feature/AuthRateLimitTest.php --compact --no-tia` | ❌ W0 | ⬜ pending |
| 01-01-05 | 01 | 1 | QLTY-01 | N/A | phpstan.neon excludePaths removed (D-11) — module tests analyzed, 0 errors | static | `composer types:check` | ✅ existing | ⬜ pending |
| 01-01-06 | 01 | 1 | QLTY-02 | N/A | 100% type coverage incl. new module test file | static | `composer test:quality` | ❌ W0 | ⬜ pending |

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
