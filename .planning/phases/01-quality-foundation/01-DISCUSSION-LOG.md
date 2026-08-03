# Phase 1: Quality Foundation - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-08-03
**Phase:** 1-Quality Foundation
**Areas discussed:** Verifikasi rate-limit, Lokasi & bentuk contract test, Enforcement failing-fast, Cakupan PHPStan test files, Status sudah hijau

---

## Lingkup Verifikasi Rate-Limit

| Option | Description | Selected |
|--------|-------------|----------|
| Hanya login | Route login saja, bukti API-03 terpenuhi | |
| Semua throttle:auth | login, register, forgot-password, reset-password — sesuai definisi limiter auth | ✓ |
| Ketiga limiter class | auth + api + authenticated, coverage penuh tapi menyentuh route phase lain | |

**User's choice:** Semua route `throttle:auth`
**Notes:** API-03 membahas "Auth routes" — fokus pada `throttle:auth` sesuai definisi limiter.

| Option | Description | Selected |
|--------|-------------|----------|
| Semua 3 header | Limit + Remaining + Reset, nilai aktual dari config | |
| Limit + Remaining saja | Lebih ringkas | |
| Cukup Remaining berkurang | Fokus perilaku | |

**User's choice:** Semua 3 header dengan nilai aktual config
**Notes:** Saat Postman testing, user menemukan hanya `X-RateLimit-Limit` dan `X-RateLimit-Remaining` pada 200. Diperiksa source Laravel — `ThrottleRequests::getHeaders()` hanya menambah `Retry-After`/`X-RateLimit-Reset` saat 429. Disesuaikan: assert Limit + Remaining pada 200, keempat header pada 429.

| Option | Description | Selected |
|--------|-------------|----------|
| Retry-After + ProblemResponse penuh | status 429, body ProblemResponse, header Retry-After | ✓ |
| Hanya status + body | 429 + bentuk, tanpa header Retry-After | |
| Full retry flow | Menunggu/simulasi reset, verifikasi request berikutnya sukses | |

**User's choice:** Retry-After + ProblemResponse penuh, skenario per-email dan per-IP terpisah

| Option | Description | Selected |
|--------|-------------|----------|
| Override config per test | config()->set() limit kecil, request berlebih | ✓ |
| Request limit asli | 5x/10x request sesuai nilai default | |
| Kombinasi | Override untuk IP, asli untuk email | |

**User's choice:** Override config per test

---

## Lokasi & Bentuk Contract Test

| Option | Description | Selected |
|--------|-------------|----------|
| Buat tests/Feature/Contract/ | Direktori khusus verifikasi kontrak end-to-end | |
| Tetap di struktur sekarang | Unit test respc + feature test di lokasi yang ada | ✓ |
| Snapshot-based | Fixture JSON bentuk response | |

**User's choice:** Tetap di struktur sekarang
**Notes:** Project sudah punya konvensi unit vs feature yang jelas.

| Option | Description | Selected |
|--------|-------------|----------|
| Biarkan apa adanya | Unit test cukup untuk bentuk dasar | ✓ |
| Perluas unit test | Tambah kasus 400/401/403/404/422, type URI | |
| Perluas + feature test | Kombinasi | |

**User's choice:** Biarkan apa adanya

| Option | Description | Selected |
|--------|-------------|----------|
| Hanya rate-limit | Fokus sempit API-03 | |
| Rate-limit + sukses | Verifikasi SuccessResponse via route nyata, tutup API-02 end-to-end | ✓ |
| Rate-limit + sukses + error | Termasuk 422 ProblemResponse | |

**User's choice:** Rate-limit + satu alur sukses login

| Option | Description | Selected |
|--------|-------------|----------|
| tests/Feature/Http | Root test dir | |
| modules/IAM/Tests/Feature | Lokasi module test (infra sudah ada) | ✓ |
| Keduanya | Tersebar | |

**User's choice:** `modules/IAM/Tests/Feature` — user bertanya ini selama diskusi; dikonfirmasi infrastruktur sudah siap (Pest.php bind + phpunit Modules suite + helpers auto-load).

| Option | Description | Selected |
|--------|-------------|----------|
| Satu file | `AuthRateLimitTest.php` dengan describe() per | ✓ |
| File per route | LoginRateLimitTest, dst | |
| Satu file + dataset | Dataset terpusat | |

**User's choice:** Satu file AuthRateLimitTest.php dengan describe() per route

## Enforcement "Failing-Fast"

| Option | Description | Selected |
|--------|-------------|----------|
| Cukup apa adanya | test:quality manual + CI ci:check | ✓ |
| Pre-commit hook | Autorun quality gate | |
| quality:check command | Perintah terpusat | |

**User's choice:** Cukup apa adanya — sesuai project philosophy "not overengineered"

| Option | Description | Selected |
|--------|-------------|----------|
| Tidak perlu | CI ci:check jadi bukti otomatis | ✓ |
| Arch rule | Tamabahkan rule ArC untuk composer scripts | |
| Dokumen di docs/ | Catat baseline | |

**User's choice:** Tidak perlu bukti khusus

| Option | Description | Selected |
|--------|-------------|----------|
| Gate hijau + gap tertutup | Baseline hijau + feature test rate-limit lolos | ✓ |
| Hanya gap | Hanya tulis feature test | |
| Termasuk hardening | Proses juga memperbaiki temuan | |

**User's choice:** Gate hijau + gap tertutup

| Option | Description | Selected |
|--------|-------------|----------|
| Perbaiki langsung | Kontrak source of truth, fix di phase | ✓ |
| Catat isu & lanjut | Deferred | |
| Per kasus | Kecil langsung, besar catat | |

**User's choice:** Perbaiki langsung di phase ini

## Cakupan PHPStan Test Files

| Option | Description | Selected |
|--------|-------------|----------|
| Biarkan exclude | modules/*/Tests tetap di-exclude | |
| Perluas ke module tests | Hapus exclude | ✓ |
| Perluas ke semua | Semua test dir | |

**User's choice:** Hapus exclude `modules/*/Tests/*` dari phpstan.neon
**Notes:** Konsisten dengan AGENTS.md — larangan modifikasi phpstan.neon hanya untuk melemahkan (level/ignore), bukan memperluas analysis.

| Option | Description | Selected |
|--------|-------------|----------|
| Pertahankan | Path tests root | ✓ |

**User's choice:** Path `tests` root dipertahankan.

## Status "Sudah Hijau"

| Option | Description | Selected |
|--------|-------------|----------|
| Verifikasi + tutup gap | Fokus bukti + contract test | ✓ |
| Verifikasi saja | Tidak menulis | |
| Verifikasi + dokumentasi | Catat nilai baseline | |

**User's choice:** Verifikasi + tutup gap

| Option | Description | Selected |
|--------|-------------|----------|
| Semua diperbaiki dalam fase | Baseline benar-benar | ✓ |
| Prioritas temuan | Kontrak wajib, lainnya tidak memblokir | |
| Catat & lanjut | Deferred | |

**User's choice:** Semua temuan diperbaiki dalam fase ini

---

## the agent's Discretion

- Test naming, describe() granularity, assertion helper usage — diputuskan planner/executor mengikuti konvensi Pest project (describe + it, toBeSuccessResponse()/toBeProblemResponse(), config()->integer()).

## Deferred Ideas

- Verifikasi rate-limit untuk `throttle:api` (email-verification, social routes, users/roles) dan `throttle:authenticated` — ke Phase 2/3.
- Test retry-flow 429 penuh (travel 60s + recovery) — dipertimbangkan, tidak dipilih (menambah runtime tanpa nilai proporsional).
- Error PHPStan di fabrikator seiring test module ditulis — diselesaikan in-phase saat muncul.