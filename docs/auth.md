# Authentication & Device Management

Sistem autentikasi dalam proyek ini menggunakan implementasi kustom yang bersih dan **Laravel Sanctum** untuk pengelolaan token API.

## 1. Fitur yang Tersedia
Seluruh endpoint autentikasi berada di bawah prefix `/api/v1/auth`.

- **Registrasi:** `POST /register`
- **Login:** `POST /login`
- **Logout:** `POST /logout`
- **Reset Password:** `POST /forgot-password` dan `POST /reset-password`
- **Verifikasi Email:** `POST /email/verification-notification` dan `GET /email/verify/{id}/{hash}`
- **Social Login:** Mendukung Google dan GitHub melalui Laravel Socialite.

### Social Login Flow
Sistem mendukung autentikasi melalui pihak ketiga (Google & GitHub).

1. **Redirect**: Frontend mengarahkan user ke `GET /api/v1/auth/social/{provider}/redirect`.
2. **Callback**: Setelah user login di provider, provider akan mengarahkan kembali ke callback URL yang akan memproses data user di `GET /api/v1/auth/social/{provider}/callback`.
3. **Success**: Server akan membuat/mencocokkan user berdasarkan email dan mengembalikan Sanctum token.

## 2. Respon JSON Standar
Semua endpoint autentikasi mengembalikan respon JSON yang konsisten menggunakan trait `ApiResponser`:

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

## 4. Lokalisasi (i18n)
Pesan error dan sukses autentikasi mendukung multi-bahasa melalui header `Accept-Language` (id/en).
