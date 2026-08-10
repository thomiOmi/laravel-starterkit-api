# TASKS - Laravel Starterkit API

Operational tracker untuk kerja harian. **Bukan** source of truth keputusan — keputusan & produk ada di `docs/` (PRD + ADR), riset historis di `.planning/`.

## Status snapshot (2026-08-11)

- Branch: `main`, HEAD `91ec339`, synced dengan origin.
- Phase 1-6 DONE, Phase 7 SKIPPED (2026-08-11, prioritas user beralih — rencana OBS-01 health tercatat di bawah).
- Baseline final: 600 tes / 2398 assertions (serial + parallel), arch 46/46, phpstan 0, coverage 100%, `composer ci:check` pass.
- Semua task roadmap & known issues ditutup sementara (2026-08-11); tidak ada keputusan desain tersisa.
- 2026-08-11: docs/ resmi jadi canonical (PRD + 22 ADR); `TASKS_2.md` dihapus — keputusan & deviasinya sudah diwariskan ke `docs/adr/`.
- Next: prioritas user di luar roadmap starterkit.

## Fokus aktif (urutan kerja)

(Isi saat mulai pengerjaan baru — prioritas user di luar roadmap.)

## Phase 7. Observability — SKIPPED (2026-08-11)

- OBS-01 health endpoint — DITUNDA (prioritas beralih, bukan dibatalkan). Rencana: module `System`, `GET /api/v1/system/health` (name `v1.system.health`), publik tanpa auth/throttle, check database (`select 1`) + cache (put/get/forget) + disk (put/get/delete) dengan latency per service, 200 `SuccessResponse` / 503 `ProblemResponse` typeKey `service_unavailable`; test unit action + feature module (`group('module:system')`). Resume bila diinginkan.
- OBS-02 Laravel Pulse — SKIP (dashboard web tidak relevan API-only; butuh dependency baru tanpa approval).

## Backlog (prioritas turun, semua ditutup sementara)

- Evaluasi `laravel/chisel` — DEFER dicabut 2026-08-11; dapat dibuka kembali.
- Phase 6 API-05: Scramble (OpenAPI) — di-skip 2026-08-10, `dedoc/scramble` tetap terpasang (lihat ADR-0019).
- Phase 7: Observability — SKIPPED 2026-08-11 (lihat atas).
- Phase 8: Modern Features & Advanced Testing — DITUTUP sementara; catatan: mutasi 210 passed (`risky` = artefak format output).

## Known issues (sementara ditutup)

- Spatie permission cache race parallel CI — DITUTUP 2026-08-11 (pengamatan; long-term TEST_TOKEN prefix); buka kembali bila perlu.
- REVIEW Phase 1: WR-01 (generic renderer header deviation), WR-02 (hardcoded `X-RateLimit-Remaining '4'`), IN-01/IN-02 — DITUTUP 2026-08-11 tanpa keputusan desain; buka kembali bila perlu.

## Definition of Done (tiap task)

- `composer lint:check`, `composer types:check`, `composer test:quality` hijau; test baru untuk tiap perubahan; tidak ada `@phpstan-ignore`.

## Pointer dokumen

- Produk & requirement: `docs/prd/`
- Keputusan arsitektur: `docs/adr/` (22 ADR)
- Konvensi teknis: `docs/` (api-standard, architecture, auth, coding-standards, module-generator, rate-limiting, rbac, testing)
- Riset & fase historis: `.planning/` (referensi GSD saja)
