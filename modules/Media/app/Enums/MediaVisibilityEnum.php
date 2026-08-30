<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

enum MediaVisibilityEnum: string
{
    case Public = 'public';
    case Private = 'private';

    /**
     * Get the human-readable, localized label for this visibility.
     */
    public function label(): string
    {
        return __('enums.'.class_basename(self::class).'.'.$this->value);
    }
}
