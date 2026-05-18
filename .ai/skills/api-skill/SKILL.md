---
name: api-skill
description: "Opinionated agent skill for building production-ready REST APIs in Laravel 13+ (Standard 2026)."
---

# API Skill for Laravel (Standard 2026)

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel 13+. All guidance here is prescriptive.

---

## 1. Architectural Foundations

- **Domain-Driven Modular Architecture**: All logic resides in `modules/{Module}/`.
- **Stateless by Design**: APIs must be stateless. No sessions, only Sanctum tokens.
- **Strict Models**: `Model::shouldBeStrict(!app()->isProduction())` must be enabled in `AppServiceProvider`.
- **Flexible Identifiers**: Support for Integer, UUID, atau ULID. Pilih satu yang konsisten dalam sebuah modul.

## 2. PHP 8.4 & Modern Patterns

- **Property Hooks**: Wajib digunakan pada **Payloads** untuk transformasi data sederhana (misal: `trim`, `strtolower`) guna mengurangi boilerplate.
- **Final & Readonly**: Semua class (Controller, Action, Payload, Service) wajib menggunakan `final`. Action dan Controller wajib `readonly`.
- **Strict Types**: Setiap file wajib diawali dengan `declare(strict_types=1);`.

## 3. Directory Structure (Uppercase V1)

Struktur modul tetap mengikuti pola ini:
- **Routes**: `modules/{Module}/Routes/V1.php`
- **Controllers**: `modules/{Module}/Controllers/V1/`
- **Payloads**: `modules/{Module}/Payloads/V1/`
- **Requests**: `modules/{Module}/Requests/V1/`
- **Actions**: `modules/{Module}/Actions/`
- **Filters**: `modules/{Module}/Filters/`
- **Resources**: `modules/{Module}/Resources/`
- **Tests**: `modules/{Module}/Tests/Feature/V1/` & `modules/{Module}/Tests/Architecture/`

## 4. Request Lifecycle & Observability

1.  **Trace ID**: Setiap request harus memiliki `trace_id` yang dikelola via Laravel **Context**. `trace_id` harus muncul di header respons (`X-Trace-ID`) dan setiap baris log.
2.  **Force JSON**: Gunakan middleware `ForceJsonResponse` untuk memastikan semua respons adalah JSON.
3.  **Throttling**: Wajib menggunakan `throttle:api` pada semua route.

## 5. Single-Action Controllers

- **Invokable Only**: Gunakan hanya `__invoke()`.
- **Dependency Injection**: Gunakan constructor injection. Dilarang menggunakan Facades atau helper `app()` di dalam controller.
- **Attributes Over DocBlocks**: Gunakan **PHP Attributes** untuk dokumentasi Scribe dan metadata lainnya.

## 6. The Action Pattern & Laravel 13 Features

- **Atomic Actions**: Satu action untuk satu operasi basis data atau aturan bisnis spesifik.
- **Orchestrator**: Gunakan orchestrator action untuk alur kerja yang kompleks.
- **Transactions**: Wajib menggunakan `$database->transaction()` untuk semua operasi tulis.
- **Defer & Concurrency**:
    - Gunakan `defer()` untuk tugas non-kritis pasca-respons (seperti kirim email/notifikasi).
    - Gunakan `Concurrency::run()` untuk menjalankan tugas I/O bound yang independen secara paralel.

## 7. Payloads (DTOs) with Property Hooks

Payloads adalah objek data yang dikirim dari Request ke Action.
- Gunakan **Property Hooks** untuk sanitasi data.
- Hindari array manipulation manual di controller.

## 8. Filtering (BaseFilter)

- **No Third-Party Query Builders**: Gunakan sistem `BaseFilter` internal.
- **Implementation**: Class filter harus meng-extend `BaseFilter<Model>` untuk menjamin *Type Safety*.

## 9. Automated Quality (Pest Arch)

- **Architecture Testing**: Wajib menyertakan Pest Arch untuk memvalidasi:
    - Tidak ada akses langsung Model dari Controller.
    - Semua Controller & Action adalah `final`.
    - Tidak ada penggunaan `env()` di luar file config.

## 10. Standardized Responses

- **Success**: Gunakan `JsonDataResponse`.
- **Error**: RFC 9457 Problem Details via `ProblemResponse`.
- **HTTP Status**: Gunakan konstanta `Symfony\Component\HttpFoundation\Response::HTTP_*`.

---

## Anti-Patterns

- ❌ Dilarang menggunakan logic di dalam Model (kecuali scope).
- ❌ Dilarang menggunakan Multi-action Controllers.
- ❌ Dilarang melewatkan `declare(strict_types=1);`.
- ❌ Dilarang menggunakan `app()` helper atau Facades jika injection memungkinkan.
- ❌ Dilarang mengembalikan model atau array mentah (wajib API Resource).
