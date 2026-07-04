# KNOWLEDGE.md

**Project:** Laravel Starterkit API
**Stack:** Laravel 13 · PHP 8.4 · MySQL · Laravel Sanctum · Spatie Permission · Laravel Socialite · Laravel Pint · Pest · PHPStan · Laravel Pennant · Redis · Laravel Tinker · Laravel Pao · Laravel Pail
**Created:** 2026-06-28
**Last Updated:** 2026-06-29

---

# Architecture Manifesto

This project follows a **Modular Monolith** architecture with a focus on high scalability and maintainability without over-engineering.

### Matriks Pola Arsitektur: Must Do vs Must Not Do

| Kategori | Must Do (Lean & Scalable) | Must Not Do (Over-Engineered) |
| :--- | :--- | :--- |
| **Data Access** | Gunakan **Eloquent Query Scopes** di dalam Model untuk logika query yang sering dipakai. | Jangan membuat **Repository Pattern** jika isinya hanya membungkus `Model::find()` atau `Model::all()`. |
| **Logika Bisnis** | Gunakan **Action Classes** untuk tugas tunggal yang kompleks (misal: `RegisterUserAction`). | Jangan membuat Action untuk operasi CRUD satu baris yang cukup di-handle model langsung. |
| **Orchestration** | Gunakan **Domain Services** untuk logika yang melibatkan banyak model atau integrasi pihak ketiga. | Jangan menumpuk logika "kebijakan aplikasi" yang kompleks di dalam Model (*Fat Model*). |
| **Data Transfer** | Gunakan **FormRequest** untuk validasi HTTP. Gunakan **Payloads (DTO)** untuk semua panggilan Action. | Jangan buat DTO untuk setiap *request* HTTP sederhana jika datanya sudah tersedia di `FormRequest`. |
| **API Response** | Wajib menggunakan **API Resources** untuk memisahkan struktur database dengan kontrak respons. | Dilarang mengembalikan *raw response* `response()->json()` secara manual di Controller. |
| **Modul** | Komunikasi antar-modul wajib melalui **Contracts** (Sinkron) atau **Events** (Asinkron). | Dilarang mengimpor Model atau kelas internal dari modul lain secara langsung (*tight coupling*). |
| **Database** | Gunakan **Eloquent** untuk 90% kasus bisnis karena kemudahan DX dan optimasinya. | Jangan gunakan **Query Builder** mentah hanya karena takut Eloquent "lambat" tanpa bukti data *latency*. |

---

### Module Isolation Rules

1.  **Zero Cross-Module Model Import:** Modul A dilarang mengimpor Model dari Modul B.
2.  **Contract-First (Synchronous):** Jika Modul A butuh data/proses dari Modul B secara instan, gunakan Interface di `app/Contracts`.
3.  **Event-Driven (Asynchronous):** Untuk proses *side-effect*, lemparkan Event. Modul lain akan menangani via Listeners.
4.  **Shared Layer:** Hanya folder `app/` yang boleh diakses oleh semua modul.

---

### Industry-Standard API Practices

-   **RFC 9457 (Problem Details):** Semua error response wajib mengikuti standar RFC 9457 menggunakan `ProblemResponse`.
-   **Rate Limit Transparency:** API wajib mengirimkan header `X-RateLimit-*` untuk membantu client mengelola throttling.
-   **Idempotency:** Gunakan header `Idempotency-Key` untuk operasi `POST` yang sensitif (seperti pembuatan transaksi).
-   **Stream Responses:** Gunakan `StreamedResponse` untuk data besar (>1000 rows) atau file export guna menjaga efisiensi memori.

---

## Decisions

### Sanctum Bearer Token over JWT — 2026-06-29
**Decision:** Use Laravel Sanctum Bearer tokens instead of JWT.
**Reason:** Supports per-device session management (revoke, list). JWT is stateless and harder to revoke individually.

---

## Conventions

-   **Final Classes:** Semua class (Action, Controller, Service) wajib menggunakan keyword `final`.
-   **Property Hooks:** Gunakan PHP 8.4 Property Hooks untuk logic derived di Model & Payload (menggantikan Attribute getter/setter).
-   **Strict Typing:** Wajib menggunakan native type hints untuk semua parameter, return type, dan class properties.

---

## Update Log
- 2026-06-29: Massive refactor of KNOWLEDGE.md to include Architecture Manifesto, Module Isolation, and Industry API Standards.

---

# Technical Standards (PHP 8.4 & Laravel 13)

### Property Hooks
Gunakan Property Hooks untuk menggantikan `Attribute` getter/setter.
- **Kelebihan:** Type-safety native, sintaks lebih bersih, performa lebih baik.
- **Contoh Model:**
```php
public string $fullName {
    get => "{$this->first_name} {$this->last_name}";
    set(string $value) {
        [$this->first_name, $this->last_name] = explode(' ', $value, 2);
    }
}
```

### Immutability & Safety
- **Final Classes:** Gunakan `final` untuk semua class kecuali jika class tersebut dirancang untuk di-extend (seperti Base Action).
- **Readonly Properties:** Gunakan `readonly` untuk semua properti di Actions dan Payloads/DTOs.
- **Constructor Promotion:** Selalu gunakan constructor promotion untuk dependency injection.

---

# API Excellence Standards

### RFC 9457 (Problem Details)
Semua error response (4xx, 5xx) wajib menggunakan `ProblemResponse`.
- **Required Fields:** `type`, `title`, `status`, `detail`.
- **Optional Fields:** `instance`, `errors` (untuk validation).

### Idempotency
- **Header:** `Idempotency-Key` (UUID v4).
- **Behavior:** Jika key ditemukan di cache, kembalikan respons lama tanpa memproses ulang logika bisnis.

### Streaming & Performance
- **Large Data:** Gunakan `fast-excel` atau `StreamedResponse` untuk ekspor data besar.
- **Sparse Fields:** Gunakan `select()` atau `$request->only()` di JSON Resources untuk membatasi data yang dikirim ke client.

---

# Pragmatic Industry Standards

### Media & File Handling
- **Security:** Gunakan **Signed URLs** untuk akses file private. Link harus kadaluarsa dalam waktu singkat (5-15 menit).
- **Consistency:** Response media wajib mengembalikan object lengkap: `url`, `thumbnail_url` (opsional), `file_name`, `size_bytes`, dan `mime_type`.
- **Storage:** Gunakan abstraksi `Storage::disk()` agar mudah berpindah dari `local` ke `s3` atau `r2`.

### Health & Monitoring
- **Endpoint:** Sediakan endpoint `/up` (Laravel default) atau `/health` untuk monitoring uptime dan status dependensi (DB, Redis).
- **Logging:** Gunakan `Log::channel('security')` untuk mencatat aktivitas mencurigakan atau kegagalan auth yang berulang.

### Performance & DX
- **N+1 Prevention:** Selalu gunakan `Model::preventLazyLoading(!app()->isProduction())` di development.
- **Sparse Fields:** Dukung sparse field selection via query parameter (misal: `?fields=id,name`) pada listing endpoint yang berat.
