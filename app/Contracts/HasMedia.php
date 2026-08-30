<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Marker interface for models that can have attached media.
 *
 * Implementors may define registerMediaCollections() and
 * registerMediaConversions() to declare single-file collections
 * and conversion definitions.
 */
interface HasMedia
{
    /**
     * Register the media collections for the model.
     */
    public function registerMediaCollections(): void;

    /**
     * Register the media conversions for the model.
     */
    public function registerMediaConversions(mixed $media = null): void;
}
