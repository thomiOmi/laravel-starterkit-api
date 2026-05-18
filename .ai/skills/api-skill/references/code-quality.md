# Code Quality & Standards (2026)

Setiap file PHP dalam proyek harus memenuhi standar ini tanpa pengecualian.

---

## 1. PHP 8.4 Standards

- **declare(strict_types=1)**: Wajib di setiap file sebagai pernyataan pertama.
- **Final Classes**: Gunakan `final` pada semua class kecuali jika didesain untuk inheritance.
- **Readonly Classes**: Gunakan `readonly` pada class yang bersifat immutable (Controller, Action, Payload).
- **Property Hooks**: Gunakan untuk transformasi data sederhana di level properti.
- **Constructor Property Promotion**: Gunakan jika memungkinkan untuk kebersihan kode.

## 2. Laravel 13 Features

- **defer()**: Gunakan untuk mengeksekusi kode setelah respons dikirim ke user (Side effects non-kritis).
- **Context**: Gunakan `Illuminate\Support\Facades\Context` untuk menyimpan state global per request (misal: `trace_id`).
- **Concurrency**: Gunakan `Concurrency::run()` untuk paralelisme tugas I/O bound.

## 3. Documentation (PHP Attributes)

Kami meninggalkan DocBlocks untuk metadata dan beralih ke **PHP Attributes**.

- **Scribe**: Gunakan attribute dari `Knuckles\Scribe\Attributes\*`.
- **Validation**: Gunakan Form Request `rules()` method, namun pertimbangkan Attributes jika di masa depan didukung secara native.

## 4. Observability (Trace ID)

Standardisasi pelacakan request:
1.  **Header**: Respons API harus menyertakan `X-Trace-ID`.
2.  **Context**: Simpan `trace_id` di Laravel Context saat awal request (Middleware).
3.  **Logs**: Pastikan setiap log menyertakan `trace_id` dari Context.

## 5. Naming Conventions

- **Payloads**: Gunakan akhiran `Payload` (bukan `DTO`).
- **Actions**: Gunakan akhiran `Action` (misal: `StoreUserAction`).
- **Controllers**: Gunakan akhiran `Controller` dan gunakan Single Action (`__invoke`).
- **Versions**: Folder versi wajib huruf besar (V1, V2).

## 6. Testing Strategy

- **Pest**: Framework pengujian default.
- **Arch Testing**: Wajib untuk menjaga integritas arsitektur.
- **Factories**: Wajib digunakan untuk semua state data dalam pengujian.
