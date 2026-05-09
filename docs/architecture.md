# Arsitektur Proyek

Proyek ini menggunakan arsitektur modular yang dirancang untuk mendukung aplikasi web (SPA) dan aplikasi mobile.

## Aliran Data Request

Berikut adalah diagram alir bagaimana sebuah request diproses di dalam sistem ini:

```mermaid
sequenceDiagram
    participant Client as SPA / Mobile Client
    participant Middleware as Middleware (Auth)
    participant Controller as Controller
    participant Action as Action
    participant Repo as Repository
    participant DB as Database

    Client->>Middleware: Request with Bearer Token
    Middleware->>Middleware: Authenticate User (Sanctum)
    Middleware->>Controller: Forward Request
    Controller->>Action: Execute Business Logic
    Action->>Repo: Data Access Request
    Repo->>DB: Query
    DB-->>Repo: Result
    Repo-->>Action: Model/Collection
    Action-->>Controller: Result Data
    Controller-->>Client: Standardized JSON Response
```

## Komunikasi Antar Modul

Setiap modul bersifat mandiri (*self-contained*), namun dapat berinteraksi satu sama lain melalui:
1. **Service Providers:** Untuk mendaftarkan fitur, migrasi, dan rute.
2. **Common Models:** Modul seperti `User` sering dirujuk oleh modul lain.
3. **Feature Flags:** Menggunakan Laravel Pennant untuk kontrol fitur yang dinamis antar user.

## Standar Kode

- **Strict Typing:** Semua file wajib menggunakan `declare(strict_types=1);`.
- **Logic Placement:** Controller hanya bertugas menerima input dan mengembalikan respon. Logika bisnis wajib diletakkan di kelas **Action**.
- **Data Access:** Semua interaksi database wajib melalui **Repository**.
