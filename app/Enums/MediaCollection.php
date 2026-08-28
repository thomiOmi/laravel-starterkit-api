<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Well-known media collections used across modules.
 */
enum MediaCollection: string
{
    case Avatars = 'avatars';
    case Default = 'default';

    /**
     * Determine whether the collection allows only a single file per model.
     */
    public function isSingleFile(): bool
    {
        return $this === self::Avatars;
    }

    /**
     * Get the allowed MIME extensions for the collection.
     *
     * @return list<string>
     */
    public function allowedMimes(): array
    {
        return match ($this) {
            self::Avatars => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'],
            default => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'],
        };
    }

    /**
     * Get the human-readable, localized label for this collection.
     */
    public function label(): string
    {
        return __('enums.'.class_basename(self::class).'.'.$this->value);
    }
}
