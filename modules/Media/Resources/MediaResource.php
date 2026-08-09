<?php

declare(strict_types=1);

namespace Modules\Media\Resources;

use App\Concerns\FormatDate;
use App\Enums\MediaVisibilityEnum;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

/**
 * @property-read Media $resource
 *
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    use FormatDate;

    /** Minutes a private file's signed URL stays valid. */
    private const int SIGNED_URL_TTL_MINUTES = 15;

    /**
     * @return array{id: string, disk: ?string, visibility: ?string, mime_type: ?string, size: ?int, meta: array<string, mixed>|null, url: ?string, signed_url: ?string, expires_at: ?string, uploaded_by: ?string, uploaded_by_user: array{id: string, name: ?string}|MissingValue, created_at: ?string, updated_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();

        $disk = array_key_exists('disk', $attributes) ? $this->resource->disk : null;

        $url = null;
        $signedUrl = null;
        $expiresAt = null;

        if (is_string($disk) && $disk !== '') {
            /** @var FilesystemAdapter $storage */
            $storage = Storage::disk($disk);

            if ($disk === 'local') {
                $expiresAt = now()->addMinutes(self::SIGNED_URL_TTL_MINUTES);
                $signedUrl = $storage->temporaryUrl($this->resource->path, $expiresAt);
            } else {
                $url = $storage->url($this->resource->path);
            }
        }

        return [
            'id' => $this->resource->id,
            'disk' => $disk,
            'visibility' => $disk === 'local' ? MediaVisibilityEnum::Private->value : MediaVisibilityEnum::Public->value,
            'mime_type' => array_key_exists('mime_type', $attributes) ? $this->resource->mime_type : null,
            'size' => array_key_exists('size', $attributes) ? $this->resource->size : null,
            'meta' => array_key_exists('meta', $attributes) ? $this->resource->meta : null,
            'url' => $url,
            'signed_url' => $signedUrl,
            'expires_at' => $expiresAt !== null ? $this->formatDate($expiresAt) : null,
            'uploaded_by' => array_key_exists('uploaded_by', $attributes) ? $this->resource->uploaded_by : null,
            'uploaded_by_user' => $this->getUploadedByUser(),
            'created_at' => array_key_exists('created_at', $attributes) ? $this->formatDate($this->resource->created_at) : null,
            'updated_at' => array_key_exists('updated_at', $attributes) ? $this->formatDate($this->resource->updated_at) : null,
        ];
    }

    /**
     * @return array{id: string, name: ?string}|MissingValue
     */
    private function getUploadedByUser(): array|MissingValue
    {
        if (! $this->resource->relationLoaded('uploadedBy')) {
            return new MissingValue;
        }

        $user = $this->resource->uploadedBy;

        if ($user === null) {
            return new MissingValue;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
}
