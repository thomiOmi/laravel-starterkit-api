# Authentication & Device Management

Sistem autentikasi menggunakan **Laravel Sanctum** yang telah dimodifikasi untuk mendukung skenario SaaS Multi-tenant dan manajemen perangkat.

## 1. Registrasi & Login
- **Registrasi:** User dapat mendaftar melalui `POST /auth/register`. Validasi email bersifat unik per tenant.
- **Login:** Menggunakan `POST /auth/login`. Respon akan menyertakan `access_token` dan data user.

## 2. Manajemen Perangkat (Multi-Device)
Secara default, sistem mendukung login dari banyak perangkat sekaligus (**Multi-Device**). Setiap login akan menghasilkan token baru tanpa menghapus token perangkat lain.

### Cara Mengubah ke Single-Device:
Jika Anda ingin user hanya bisa login di satu perangkat saja (login baru akan menendang login lama), edit file `modules/Auth/Actions/LoginAction.php`:

```php
// Hapus komentar pada baris ini:
$user->tokens()->delete();
```

## 3. Email Verification
Sistem mendukung verifikasi email secara otomatis setelah registrasi. Pesan email dikirim secara asinkron (queue) menggunakan notification standar Laravel yang telah dilokalisasi.

## 4. User Profile
Endpoint `GET /auth/me` menyediakan informasi lengkap user yang sedang login, termasuk daftar permission dan role yang dimiliki.
