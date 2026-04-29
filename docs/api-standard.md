# API Standards & Best Practices

Project ini mengikuti standar ketat dalam pengembangan API untuk menjaga konsistensi dan skalabilitas.

## 1. API Versioning
Versioning dikelola melalui URL prefix:
- `/api/v1/`

Route setiap modul dipisahkan berdasarkan versi dalam file `modules/{Module}/Routes/v1.php`. Hal ini memungkinkan Anda menjalankan beberapa versi API secara bersamaan tanpa konflik.

## 2. Standard JSON Response
Semua respon API menggunakan trait `App\Traits\ApiResponser` untuk menjamin format yang seragam.

### Success Response (200/201):
```json
{
    "status": "success",
    "message": "Resource created successfully",
    "data": { ... },
    "meta": {
        "api_version": "v1"
    }
}
```

### Error Response (4xx/5xx):
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

## 3. Global Error Handling
Kesalahan umum ditangani secara otomatis di `bootstrap/app.php` untuk memastikan respon selalu dalam format JSON, termasuk:
- **404 Not Found** (Route atau Model)
- **401 Unauthenticated**
- **403 Unauthorized**
- **422 Validation Error**
- **500 Internal Server Error**

## 4. Bulk Actions
Sistem mendukung aksi massal (bulk) untuk efisiensi:
- Endpoint: `POST /api/v1/{resource}/bulk`
- Action: `delete`, `update`, `restore`, `forceDelete`.
- Logic: Diimplementasikan di layer Repository melalui method `bulk()`.
