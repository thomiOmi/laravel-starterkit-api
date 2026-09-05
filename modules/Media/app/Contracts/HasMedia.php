<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaCollection;
use Modules\Media\Support\MediaConversion;
use Modules\Media\Support\PendingMedia;

/**
 * Contract for models that own media.
 *
 * @mixin Model
 *
 * @phpstan-require-extends Model
 */
interface HasMedia
{
    /**
     * Get the media owned by the model.
     *
     * @return MorphMany<Media, Model>
     */
    public function media(): MorphMany;

    /**
     * Register the media collections for the model.
     * Override in the model to define singleFile, accepts, etc.
     */
    public function registerMediaCollections(): void;

    /**
     * Register the media conversions for the model.
     */
    public function registerMediaConversions(?Media $media = null): void;

    public function addMediaCollection(string $name): MediaCollection;

    public function addMediaConversion(string $name): MediaConversion;

    public function getMediaCollection(string $name): ?MediaCollection;

    /**
     * @return array<string, MediaCollection>
     */
    public function getMediaCollections(): array;

    /**
     * @return array<string, MediaCollection>
     */
    public function getRegisteredMediaCollections(): array;

    /**
     * @return array<string, MediaConversion>
     */
    public function getMediaConversions(?Media $media = null): array;

    public function hasMedia(string $collection = 'default'): bool;

    /**
     * Start a fluent media attachment for the model.
     */
    public function addMedia(UploadedFile $file): PendingMedia;

    /**
     * @param  array<int, string>  $exceptIds
     */
    public function clearMediaCollectionExcept(string $collection, array $exceptIds): int;

    public function getFirstMediaUrl(string $collection = 'default', string $conversion = ''): ?string;

    public function getFallbackMediaUrl(string $collection, string $conversion = ''): ?string;

    public function getFallbackMediaPath(string $collection, string $conversion = ''): ?string;

    public function addMediaFromRequest(string $key): PendingMedia;

    /**
     * @param  array<int, string>  $keys
     * @return array<int, PendingMedia>
     */
    public function addMultipleMediaFromRequest(array $keys): array;

    /**
     * @param  array<int, string>|null  $keys
     * @return array<int, PendingMedia>
     */
    public function addAllMediaFromRequest(?array $keys = null): array;

    /**
     * @param  array<string, string>  $headers
     */
    public function addMediaFromUrl(string $url, ?string $filename = null, array $headers = []): PendingMedia;

    public function addMediaFromString(string $content, string $filename): PendingMedia;

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(string $collection = 'default'): Collection;

    public function getFirstMedia(string $collection = 'default'): ?Media;

    public function getFirstMediaPath(string $collection = 'default', string $conversion = ''): ?string;

    public function getFirstMediaSignedUrl(string $collection = 'default', ?int $ttlMinutes = null): ?string;

    public function clearMediaCollection(string $collection): int;

    /**
     * @param  array<int, string>  $orderedIds
     */
    public function reorderMedia(string $collection, array $orderedIds): void;
}
