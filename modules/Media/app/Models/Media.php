<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Media\Builders\MediaBuilder;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Enums\MediaVisibilityEnum;
use Modules\Media\Policies\MediaPolicy;

/**
 * @property string $id The unique identifier for the media item.
 * @property string|null $model_type The owning model class.
 * @property string|null $model_id The owning model key.
 * @property string $collection_name The logical collection the media belongs to.
 * @property string $disk The storage disk the file is stored on.
 * @property string $mime_type The MIME type of the stored file.
 * @property int $size The file size in bytes.
 * @property string $path The unique storage path of the file.
 * @property MediaVisibilityEnum $visibility Who may access the media.
 * @property string|null $original_name The original client file name.
 * @property string|null $original_extension The original file extension.
 * @property string|null $sha256 The SHA-256 checksum of the stored file.
 * @property array<string, mixed>|null $meta Free-form metadata (dimensions, etc.).
 * @property array<string, mixed>|null $custom_properties Application-level metadata.
 * @property int $order_column Ordering within the collection.
 * @property string|null $uploaded_by_type The uploader model class.
 * @property string|null $uploaded_by_id The uploader model key.
 */
#[Fillable(['model_type', 'model_id', 'collection_name', 'disk', 'mime_type', 'size', 'path', 'visibility', 'original_name', 'original_extension', 'sha256', 'meta', 'custom_properties', 'order_column', 'uploaded_by_type', 'uploaded_by_id'])]
#[UseEloquentBuilder(MediaBuilder::class)]
#[UseFactory(MediaFactory::class)]
#[UsePolicy(MediaPolicy::class)]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasDefaultBehavior, HasFactory;

    /**
     * Get the owning model of the media.
     *
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the uploader model of the media.
     *
     * @return MorphTo<Model, $this>
     */
    public function uploadedBy(): MorphTo
    {
        return $this->morphTo(
            name: 'uploadedBy',
            type: 'uploaded_by_type',
            id: 'uploaded_by_id',
        );
    }

    /**
     * Determine whether the media belongs to the given model.
     */
    public function belongsToModel(Model $model): bool
    {
        $modelId = $this->model_id;
        $modelType = $this->model_type;

        if (! is_string($modelId) || ! is_string($modelType)) {
            return false;
        }

        $key = $model->getKey();

        if (! is_string($key) && ! is_int($key)) {
            return false;
        }

        return $modelType === $model->getMorphClass()
            && $modelId === (string) $key;
    }

    /**
     * Determine whether the media is publicly visible.
     */
    public function isPublic(): bool
    {
        return $this->visibility === MediaVisibilityEnum::Public;
    }

    /**
     * Determine whether the media belongs to the given key via uploader.
     */
    public function isOwnedBy(string|int|null $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        $uploadedById = $this->uploaded_by_id;

        return is_string($uploadedById) && $uploadedById === (string) $userId;
    }

    /**
     * Scope a query to ordered media.
     *
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public function scopeOrdered($query): Builder
    {
        return $query->orderBy('order_column')->orderBy('created_at');
    }

    /**
     * Get the conversions for the media.
     *
     * @return HasMany<MediaConversion, $this>
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(MediaConversion::class, 'media_id');
    }

    public function hasGeneratedConversion(string $name): bool
    {
        return $this->conversions()->where('name', $name)->exists();
    }

    public function getConversion(string $name): ?MediaConversion
    {
        return $this->conversions()->where('name', $name)->first();
    }

    /**
     * Get the public URL for the media, or null for private media.
     * When a conversion name is provided, returns the conversion URL if it exists.
     */
    public function url(?string $conversion = null): ?string
    {
        if ($conversion !== null) {
            $conv = $this->getConversion($conversion);

            if ($conv !== null) {
                if ($this->isPublic()) {
                    return Storage::disk($conv->disk)->url($conv->path);
                }

                return null;
            }
        }

        if (! $this->isPublic()) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function getUrl(?string $conversion = null): ?string
    {
        return $this->url($conversion);
    }

    /**
     * Get a temporary signed URL for streaming the media.
     */
    public function signedUrl(int $ttlMinutes = 10): string
    {
        return (string) URL::temporarySignedRoute(
            'api.v1.media.file',
            now()->addMinutes($ttlMinutes),
            ['media' => $this->getKey()],
        );
    }

    public function getTemporaryUrl(\DateTimeInterface $expiration): string
    {
        return (string) URL::temporarySignedRoute(
            'api.v1.media.file',
            $expiration,
            ['media' => $this->getKey()],
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'visibility' => MediaVisibilityEnum::class,
            'meta' => 'array',
            'custom_properties' => 'array',
            'order_column' => 'integer',
        ];
    }
}
