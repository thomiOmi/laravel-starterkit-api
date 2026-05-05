# Authentication & Device Management

Sistem autentikasi dalam proyek ini menggunakan implementasi kustom yang bersih dan **Laravel Sanctum** untuk pengelolaan token API.

## 1. Fitur yang Tersedia
Seluruh endpoint autentikasi berada di bawah prefix `/api/v1/auth` dan wajib menyertakan header `X-Tenant`.

- **Registrasi:** `POST /register`
- **Login:** `POST /login`
- **Logout:** `POST /logout`
- **Reset Password:** `POST /forgot-password` dan `POST /reset-password`
- **Verifikasi Email:** `POST /email/verification-notification` dan `GET /email/verify/{id}/{hash}`
- **Two-Factor Authentication (2FA):** Implementasi kustom menggunakan Google2FA.
- **Social Login:** Mendukung Google dan GitHub melalui Laravel Socialite.

### Social Login Flow
Sistem mendukung autentikasi melalui pihak ketiga (Google & GitHub).

1. **Redirect**: Frontend mengarahkan user ke `GET /api/v1/auth/social/{provider}/redirect`.
2. **Callback**: Setelah user login di provider, provider akan mengarahkan kembali ke callback URL yang akan memproses data user di `GET /api/v1/auth/social/{provider}/callback`.
3. **Success**: Server akan membuat/mencocokkan user berdasarkan email dan mengembalikan Sanctum token.

### 2FA API Flow
Sistem mendukung Two-Factor Authentication (2FA) untuk meningkatkan keamanan akun.

#### 1. Setup 2FA
Alur untuk mengaktifkan 2FA pada akun user:
1. **Enable 2FA**: `POST /api/v1/auth/two-factor-authentication`. Memerlukan autentikasi.
2. **Get QR Code**: `GET /api/v1/auth/two-factor-qr-code`. Mengembalikan SVG QR code yang harus di-scan menggunakan aplikasi authenticator (seperti Google Authenticator).
3. **Get Secret Key**: `GET /api/v1/auth/two-factor-secret-key` (Optional). Jika user ingin memasukkan key secara manual.
4. **Confirm 2FA**: `POST /api/v1/auth/confirmed-two-factor-authentication`. Kirim `code` (OTP) untuk memverifikasi dan mengaktifkan 2FA sepenuhnya.

#### 2. Login dengan 2FA
Alur login saat 2FA aktif:
1. **Login Attempt**: Kirim email dan password ke `POST /api/v1/auth/login`.
2. **Challenge Required**: Jika 2FA aktif, server akan mengembalikan status 423 (Locked) atau respon khusus yang menunjukkan tantangan 2FA diperlukan.
3. **Submit OTP**: Kirim OTP ke `POST /api/v1/auth/two-factor-challenge` dengan body `code` (atau `recovery_code`).
4. **Success**: Jika valid, server akan mengembalikan token akses (Sanctum).

#### 3. Endpoint 2FA Lengkap
| Method | URL | Deskripsi | Body |
|--------|-----|-----------|------|
| POST | `/auth/two-factor-authentication` | Mengaktifkan/Menyiapkan 2FA | - |
| DELETE | `/auth/two-factor-authentication` | Menonaktifkan 2FA | - |
| GET | `/auth/two-factor-qr-code` | Mendapatkan QR Code (SVG) | - |
| GET | `/auth/two-factor-secret-key` | Mendapatkan Secret Key | - |
| GET | `/auth/two-factor-recovery-codes` | Mendapatkan Recovery Codes | - |
| POST | `/auth/two-factor-recovery-codes` | Regenerasi Recovery Codes | - |
| POST | `/auth/two-factor-challenge` | Menyelesaikan Tantangan 2FA (saat login) | `code` atau `recovery_code` |

> **Note**: Frontend bertanggung jawab merender SVG yang dikembalikan oleh endpoint QR Code.

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
