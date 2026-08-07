# TASKS — Laravel Starterkit API

Tracking cepat pengembangan. Source of truth tetap di `.planning/` (ROADMAP.md, REQUIREMENTS.md, STATE.md, phases/).
Dokumen ini = prioritas gabungan + status terkini, diupdate tiap kali ada kemajuan.

## Status snapshot (2026-08-07)

- Branch: `main`, HEAD `2e2e002`, working tree bersih, ahead of origin/main 32 commit.
- Phase 1 (Quality Foundation): 2/2 plans selesai, gate hijau (pint, phpstan 0 errors, 100% type coverage, 285 test / 1019 assertion).
- Phase 1 status `verifying` — tersisa 1 human verification (concurrency limiter) + findings REVIEW (WR-01, WR-02, IN-01, IN-02).
- Target: minimum 1 module baru (Media Storage), tanpa maju-mundur antar modul — setiap perubahan selesai dan hijau sebelum lanjut.

## Keputusan arsitektur (catatan permanen)

| Keputusan | Alasan |
|---|---|
| `app/` = shared vocabulary & contract | Contracts, Enums, Concerns dipakai lintas lapisan (root Database/Middleware/HTTP/Providers + semua modul). Arah dependensi modul → `app/` selalu diizinkan. |
| `Modules/*/Policies` = implementasi domain | Policy terikat model modul (`Modules\IAM\Models\User`) dan `App\Policies` tidak ada di allowlist `arch('modules should be isolated')` → wajib di module, diregistrasi via `Gate::policy()` di `IAMServiceProvider`. |
| Enums IAM tetap di `app/Enums` | `UserStatusEnum` dipakai migration root + `PermissionEnum` dipakai `app/Http/Requests/BulkActionRequest.php` — keduanya di luar allowlist arch test; memindahkan enum ke module melanggar `modules should be isolated`. Bukan inkonsistensi: satu prinsip "file root di luar allowlist tidak boleh import module code". |
| Akuntansi = rejected untuk module baru | Scope besar & domain-specific (chart of accounts, tax per negara); tidak bisa digeneralisasi jadi kit. Modul produk, bukan infrastruktur kit. |
| Media Storage = module baru pertama | Reuse tertinggi di industri (avatar, dokumen, receipt, galeri); sudah tercantum di PROJECT.md; pakai Storage bawaan Laravel tanpa dependency baru. |

## Prioritas aktif (urutan kerja)

### P0-A. Seeder IAM tunggal (anti race cache/seeder) — DONE (2026-08-07)
- [x] Gabung `UserSeeder` + `RoleSeeder` jadi satu seeder `IAMSeeder` di `Modules\IAM\Database\Seeders`.
- [x] Ikuti pola Spatie: flush cache sebelum, buat permissions → roles → users, flush setelah.
- [x] Factory state `afterCreating` assign role (`superAdmin()`, `admin()`, `user()`), hapus `assignRolesToExistingUsers()` yang query silang.
- [x] `DatabaseSeeder` panggil satu seeder saja; update pemanggil test (`BulkActionRequestTest`, `AuthRateLimitTest`, helpers `loginAsRole`).
- [x] Gate hijau: pint, phpstan 0 errors, 285 tes / 1019 assertions, type coverage 100%.

### P0-B. User status enforcement (banned/suspended/inactive)
- [ ] `LoginAction`: tolak login untuk status Banned/Suspended/Inactive (error message jelas, sesuai pola).
- [ ] Middleware `EnsureUserIsActive` (cek status pada request ter-autentikasi) + alias + daftar di route group authenticated.
- [ ] `UserRequest`/`UserPayload`: izinkan admin update status; validasi enum.
- [ ] `UserResource`: expose `status`.
- [ ] Factory: state `banned()`, `suspended()`, `inactive()`; tes tiap CRUD + login ter-block + middleware.

### P0-C. URL generation pakai helper `url()`
- [ ] `AppServiceProvider::configureEmailVerificationUrl()` — ganti string concat/parse_url dengan `Uri`/`url()->query()`; pastikan id/hash/expires/signature benar.
- [ ] `configurePasswordReset()` — `Uri::of($frontendUrl)->withQuery([...])` dengan encoding benar.
- [ ] Tes unit VerifyEmail/ResetPassword tetap hijau + tambah asersi format URL.

### P1-D. Model Policies (Spatie permission)
- [ ] Buat `UserPolicy`, `RolePolicy`, `PermissionPolicy` di `Modules\IAM\Policies`.
- [ ] Registrasi eksplisit via `Gate::policy()` di `IAMServiceProvider` (auto-discovery tidak mencakup model modul).
- [ ] Pindahkan logic `UserRequest::authorize()` ke policy; FormRequest pakai `Gate::authorize`.
- [ ] Tes: ability tiap policy (view/create/update/delete), super-admin bypass, guard mismatch 403.

### P1-E. Gap Phase 2 (Authentication) — audit & lengkapi
- [ ] AUTH-07 Change password (current password confirmation) — baru.
- [ ] AUTH-08 Delete account (self-service) — baru.
- [ ] AUTH-04 token naming (`token_name` di login) — verifikasi/implementasi.
- [ ] Verifikasi AUTH-01..06 dengan tes UAT mengikuti pola AuthRateLimitTest.

### P2-F. Module baru: Media Storage (target minimum 1)
- [ ] `php artisan make:module Media` + pola modular IAM (Actions, Controllers, Filters, Payloads, Requests, Resources).
- [ ] Model `Media` (ulid, disk, mime_type, size, path, meta), migration, factory, seeder.
- [ ] Endpoint V1: upload, list, show, delete; public vs private storage (signed URL).
- [ ] Permission `media.view/create/delete` + `MediaPolicy` (sekaligus contoh penerapan P1-D).
- [ ] Tes feature + unit per pola IAM.

## Backlog roadmap (dari .planning/ROADMAP.md — prioritas turun)

- [ ] Phase 3: Social Auth & Profile — controllers ada; gap: link/unlink, avatar MissingAttributeException (verifikasi), change email re-verify.
- [ ] Phase 4: IAM Admin — CRUD sudah ada; verifikasi success criteria (contract Identity, guard mismatch 403, cache race).
- [ ] Phase 5: Feature Flags (Pennant) — FLAG-01..02.
- [ ] Phase 6: API Hardening & Documentation — idempotency keys, Scramble (config scramble.php sudah ada).
- [ ] Phase 7: Observability — health endpoint, Pulse.
- [ ] Phase 8: Modern Features & Advanced Testing — attributes, mutation/stress/snapshot (mutasi: 210 passed; `risky` = artefak format output, bukan kegagalan).

## Known issues (dari PROJECT.md + REVIEW)

- [ ] Module unit tests `no such table: users` (migration ordering) — cek apakah masih terjadi.
- [ ] AuthTest avatar MissingAttributeException — verifikasi keberadaan test tsb (tidak ada di inventaris saat ini).
- [ ] Spatie permission cache race parallel CI — P0-A sebagian menangani; long-term TEST_TOKEN prefix.
- [ ] REVIEW Phase 1: WR-01 (generic renderer header deviation — perlu keputusan: revert atau approve+dokumentasi), WR-02 (hardcoded `X-RateLimit-Remaining '4'`), IN-01/IN-02.

## Definition of Done (tiap task)

- `composer lint:check`, `composer types:check`, `composer test:quality` hijau; test baru untuk tiap perubahan; tidak ada `@phpstan-ignore`.
