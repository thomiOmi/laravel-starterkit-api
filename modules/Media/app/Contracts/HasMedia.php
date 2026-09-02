<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaCollection;
use Modules\Media\Support\MediaConversion;

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
     * @return array<string, MediaConversion>
     */
    public function getMediaConversions(?Media $media = null): array;

    public function hasMedia(string $collection = 'default'): bool;

    /**
     * @param  array<int, string>  $exceptIds
     */
    public function clearMediaCollectionExcept(string $collection, array $exceptIds): int;

    public function getFirstMediaUrl(string $collection = 'default', string $conversion = ''): ?string;
}
