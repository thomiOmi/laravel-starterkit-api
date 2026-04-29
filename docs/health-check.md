# Health Check API

Endpoint Health Check disediakan untuk mempermudah monitoring infrastruktur sistem oleh layanan external (seperti UptimeRobot, Kubernetes, atau Prometheus).

## 1. Akses Endpoint
`GET /api/v1/health`

## 2. Layanan yang Dipantau
Endpoint ini melakukan verifikasi real-time terhadap:
- **Database:** Memastikan koneksi ke database utama aktif dan dapat melakukan query.
- **Cache:** Memastikan driver cache (Redis/File) dapat membaca dan menulis data.
- **Storage:** Memastikan direktori storage memiliki izin akses tulis (writeable).

## 3. Format Respon
Jika semua layanan normal:
```json
{
    "status": "ok",
    "timestamp": "2026-04-27T10:00:00Z",
    "services": {
        "database": {"status": "ok"},
        "cache": {"status": "ok"},
        "storage": {"status": "ok"}
    }
}
```

Jika salah satu layanan gagal, sistem akan mengembalikan HTTP Status **503 Service Unavailable** dengan detail pesan error pada layanan terkait.
