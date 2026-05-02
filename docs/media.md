# Media Library

The Media Library module provides a robust way to manage files and images with tenant isolation and automatic image processing. It is built on top of `spatie/laravel-medialibrary`.

## 🚀 Features

-   **Multi-Tenancy**: All media are automatically scoped to the current tenant.
-   **Image Manipulation**: Automatic generation of `thumb` (150x150) and `preview` (400x400) conversions for images.
-   **Polymorphic Support**: Can be attached to any model using Spatie's `HasMedia` interface.
-   **Standardized API**: Dedicated endpoints for managing media files.

## 🛠 Usage

### Uploading Media

**Endpoint**: `POST /api/v1/media`
**Headers**: `X-Tenant: {tenant_id}`
**Body (Multipart)**:
- `file`: The file to upload.
- `collection` (optional): The collection name (defaults to `default`).

### Listing Media

**Endpoint**: `GET /api/v1/media`
**Headers**: `X-Tenant: {tenant_id}`

The response will include URLs for the original file and its generated thumbnails:

```json
{
    "data": [
        {
            "id": "...",
            "file_name": "image.jpg",
            "url": "http://...",
            "thumbnails": {
                "thumb": "http://...",
                "preview": "http://..."
            }
        }
    ]
}
```

### Deleting Media

**Endpoint**: `DELETE /api/v1/media/{id}`
**Headers**: `X-Tenant: {tenant_id}`

## 🏗 Architecture

The module follows the project's standard architecture:
-   **Action**: `Modules\Media\Actions\UploadMediaAction` handles the file addition logic.
-   **Repository**: `Modules\Media\Repositories\MediaRepository` for data retrieval.
-   **Resource**: `Modules\Media\Resources\MediaResource` for standardized API responses.
-   **Isolation**: Managed via `Modules\Media\Models\Observers\MediaObserver`.

## ⚙️ Configuration

Configurations can be found in `config/media-library.php`. By default, it uses the `public` disk, which is tenant-aware in this starter kit.
