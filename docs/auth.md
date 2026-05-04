# Authentication & Device Management

Sistem autentikasi dalam proyek ini sepenuhnya menggunakan **Laravel Fortify** sebagai backend logic dan **Laravel Sanctum** untuk pengelolaan token API.

## 1. Fitur yang Tersedia
Seluruh endpoint autentikasi berada di bawah prefix `/api/v1/auth` dan wajib menyertakan header `X-Tenant`.

- **Registrasi:** `POST /register`
- **Login:** `POST /login`
- **Logout:** `POST /logout`
- **Reset Password:** `POST /forgot-password` dan `POST /reset-password`
- **Verifikasi Email:** `POST /email/verification-notification` dan `GET /email/verify/{id}/{hash}`
- **Two-Factor Authentication (2FA):** Tersedia melalui endpoint standar Fortify.

## 2. Respon JSON Standar
Meskipun menggunakan Fortify, sistem telah dikonfigurasi untuk mengembalikan respon JSON yang konsisten:

```json
{
    "status": "success",
    "message": "Login successful.",
    "data": {
        "user": { ... },
        "access_token": "...",
        "token_type": "Bearer"
    }
}
```

## 3. Manajemen Perangkat (Multi-Device)
Setiap login menghasilkan `PersonalAccessToken` baru yang mencatat informasi perangkat.

- **Daftar Perangkat:** `GET /auth/devices`
- **Logout Perangkat Spesifik:** `DELETE /auth/devices/{id}`
- **Logout Perangkat Lain:** `POST /auth/devices/logout-others`

## 4. Keamanan Multi-tenancy
Setiap token bersifat tenant-aware. User dari Tenant A tidak dapat menggunakan tokennya untuk mengakses data milik Tenant B, meskipun memiliki ID user yang sama (jika ada).

## 5. Lokalisasi (i18n)
Pesan error dan sukses autentikasi mendukung multi-bahasa melalui header `Accept-Language` (id/en).
