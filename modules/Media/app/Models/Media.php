<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
use Modules\Media\Observers\MediaObserver;
use Modules\Media\Policies\MediaPolicy;
use Modules\Media\Support\PathGenerator\MediaPathGenerator;

/**
 * @property string $id The unique identifier for the media item.
 * @property string|null $model_type The owning model class.
 * @property string|null $model_id The owning model key.
 * @property string $collection_name The logical collection the media belongs to.
 * @property string $name The file name without extension.
 * @property string $file_name The file name with extension.
 * @property string $disk The storage disk the file is stored on.
 * @property string|null $conversions_disk The disk for conversions.
 * @property string $mime_type The MIME type of the stored file.
 * @property int $size The file size in bytes.
 * @property MediaVisibilityEnum $visibility Who may access the media.
 * @property string|null $original_name The original client file name.
 * @property string|null $original_extension The original file extension.
 * @property string|null $sha256 The SHA-256 checksum of the stored file.
 * @property array<string, mixed>|null $manipulations JSON manipulations per conversion.
 * @property array<string, mixed>|null $custom_properties Application-level metadata.
 * @property array<string, mixed>|null $generated_conversions JSON map of generated conversions.
 * @property array<int, array{path: string, size: int|null}>|null $responsive_images JSON responsive data keyed by width.
 * @property array<string, mixed>|null $meta Free-form metadata (dimensions, etc.).
 * @property int $order_column Ordering within the collection.
 * @property string|null $uploaded_by_type The uploader model class.
 * @property string|null $uploaded_by_id The uploader model key.
 */
#[Fillable(['model_type', 'model_id', 'collection_name', 'name', 'file_name', 'disk', 'conversions_disk', 'mime_type', 'size', 'visibility', 'original_name', 'original_extension', 'sha256', 'manipulations', 'custom_properties', 'generated_conversions', 'responsive_images', 'meta', 'order_column', 'uploaded_by_type', 'uploaded_by_id'])]
#[UseEloquentBuilder(MediaBuilder::class)]
#[UseFactory(MediaFactory::class)]
#[UsePolicy(MediaPolicy::class)]
#[ObservedBy([MediaObserver::class])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasDefaultBehavior, HasFactory;

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->name) && ! empty($model->file_name)) {
                $model->name = pathinfo($model->file_name, PATHINFO_FILENAME);
            }
            if (empty($model->file_name) && ! empty($model->name)) {
                $ext = $model->original_extension ?? 'bin';
                $model->file_name = $model->name.'.'.$ext;
            }
        });
    }

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
     * When a conversion name is provided, returns the conversion URL if it exists, otherwise null.
     */
    public function url(?string $conversion = null): ?string
    {
        if ($conversion !== null) {
            $conv = $this->getConversion($conversion);

            if ($conv === null) {
                return null;
            }

            if (! $this->isPublic()) {
                return null;
            }

            return $this->versionedUrl(Storage::disk($conv->disk)->url($conv->path));
        }

        if (! $this->isPublic()) {
            return null;
        }

        $path = $this->getPath();

        if ($path === null) {
            return null;
        }

        return $this->versionedUrl(Storage::disk($this->disk)->url($path));
    }

    public function getUrl(?string $conversion = null): ?string
    {
        return $this->url($conversion);
    }

    /**
     * Get a temporary signed URL for streaming the media.
     *
     * A null TTL falls back to the media.temporary_url_default_lifetime config.
     */
    public function signedUrl(?int $ttlMinutes = null): string
    {
        $ttlMinutes ??= config()->integer('media.temporary_url_default_lifetime', 10);

        return (string) URL::temporarySignedRoute(
            'api.v1.media.file',
            now()->addMinutes($ttlMinutes),
            ['media' => $this->getKey()],
        );
    }

    public function getTemporaryUrl(\DateTimeInterface $expiration, ?string $conversion = null): string
    {
        // For conversions, still use the same signed route - the file controller serves the original,
        // but for conversion we could generate a separate signed conversion route in the future.
        // For now, keep simple: signed url for the media itself.
        return (string) URL::temporarySignedRoute(
            'api.v1.media.file',
            $expiration,
            ['media' => $this->getKey()],
        );
    }

    public function getFullUrl(?string $conversion = null): ?string
    {
        return $this->url($conversion);
    }

    public function getPath(?string $conversion = null): ?string
    {
        $generator = $this->resolvePathGenerator();

        if ($conversion === null || $conversion === '') {
            return $generator->getPath($this);
        }

        if (! $this->hasGeneratedConversion($conversion)) {
            return null;
        }

        return $generator->getPathForConversions($this, $conversion);
    }

    /**
     * Resolve the path generator, honoring per-model overrides from
     * the media.custom_path_generators config (keyed by model class
     * or morph alias as stored in media.model_type).
     */
    private function resolvePathGenerator(): MediaPathGenerator
    {
        /** @var array<array-key, mixed> $overrides */
        $overrides = config()->array('media.custom_path_generators', []);

        $modelType = $this->model_type;

        if (is_string($modelType) && $modelType !== '' && array_key_exists($modelType, $overrides)) {
            $class = $overrides[$modelType];

            if (is_string($class) && is_a($class, MediaPathGenerator::class, true)) {
                $custom = app($class);

                if ($custom instanceof MediaPathGenerator) {
                    return $custom;
                }
            }
        }

        /** @var MediaPathGenerator $generator */
        $generator = app(MediaPathGenerator::class);

        return $generator;
    }

    /**
     * Attach a ?v=xx cache-busting query string when version_urls is enabled.
     */
    private function versionedUrl(string $url): string
    {
        if (! config()->boolean('media.version_urls', false)) {
            return $url;
        }

        $version = $this->updated_at === null ? time() : $this->updated_at->timestamp;
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.$version;
    }

    public function getFullUrlOrFallback(?string $conversion = null, ?string $fallback = null): ?string
    {
        return $this->url($conversion) ?? $fallback;
    }

    /**
     * Build a srcset attribute value from generated responsive images.
     *
     * Returns null for private media or when no responsive images exist.
     */
    public function getSrcset(): ?string
    {
        if (! $this->isPublic()) {
            return null;
        }

        $responsive = $this->responsive_images;

        if (! is_array($responsive) || $responsive === []) {
            return null;
        }

        $entries = [];

        foreach ($responsive as $width => $info) {
            if ($info['path'] === '') {
                continue;
            }

            $entries[(int) $width] = Storage::disk($this->disk)->url($info['path']).' '.((int) $width).'w';
        }

        if ($entries === []) {
            return null;
        }

        ksort($entries);

        return implode(', ', $entries);
    }

    public function getCustomProperty(string $name, mixed $default = null): mixed
    {
        $props = $this->custom_properties;

        if (! is_array($props)) {
            return $default;
        }

        return $props[$name] ?? $default;
    }

    public function setCustomProperty(string $name, mixed $value): self
    {
        $props = is_array($this->custom_properties) ? $this->custom_properties : [];
        $props[$name] = $value;
        $this->custom_properties = $props;

        return $this;
    }

    public function forgetCustomProperty(string $name): self
    {
        $props = is_array($this->custom_properties) ? $this->custom_properties : [];
        unset($props[$name]);
        $this->custom_properties = $props;

        return $this;
    }

    public function hasCustomProperty(string $name): bool
    {
        $props = $this->custom_properties;

        return is_array($props) && array_key_exists($name, $props);
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
            'manipulations' => 'array',
            'custom_properties' => 'array',
            'generated_conversions' => 'array',
            'responsive_images' => 'array',
            'order_column' => 'integer',
        ];
    }
}
