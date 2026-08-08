<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case User = 'user';

    /**
     * Get the human-readable, localized label for this role.
     */
    public function label(): string
    {
        return __('enums.'.basename(self::class).'.'.$this->value);
    }
}
