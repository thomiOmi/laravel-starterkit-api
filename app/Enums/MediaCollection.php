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
     * Get the human-readable, localized label for this collection.
     */
    public function label(): string
    {
        return __('enums.'.class_basename(self::class).'.'.$this->value);
    }
}
