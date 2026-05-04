# Webhook System

Modul Webhook memungkinkan sistem untuk mengirimkan notifikasi real-time ke sistem eksternal saat terjadi event tertentu.

## Konfigurasi

Setiap tenant dapat mendaftarkan beberapa URL webhook.

### Payload Keamanan

Setiap request webhook menyertakan header `X-Webhook-Signature` yang merupakan HMAC SHA-256 dari payload JSON menggunakan `secret` yang dikonfigurasikan oleh tenant.

```php
$signature = hash_hmac('sha256', $payload, $secret);
```

## Event yang Didukung

- `user.registered`: Dipicu saat user baru mendaftar.
- `subscription.updated`: Dipicu saat paket langganan berubah.

## Penggunaan di Kode

Untuk mengirimkan webhook dari modul lain:

```php
use Modules\Webhook\Services\WebhookService;

(new WebhookService)->dispatch('user.registered', $userData);
```

## Monitoring

Semua percobaan pengiriman webhook dicatat di tabel `webhook_calls` untuk keperluan audit dan debugging.
