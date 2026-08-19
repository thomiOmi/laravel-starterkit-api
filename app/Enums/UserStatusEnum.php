<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Banned = 'banned';

    /**
     * Determine whether a user with this status may authenticate.
     *
     * Pending accounts may still sign in so they can request a new
     * verification email; the `verified` middleware protects guarded
     * routes separately.
     */
    public function allowsAuthentication(): bool
    {
        return match ($this) {
            self::Active, self::Pending => true,
            self::Inactive, self::Suspended, self::Banned => false,
        };
    }

    /**
     * Get the human-readable, localized label for this status.
     */
    public function label(): string
    {
        return __('enums.'.class_basename(self::class).'.'.$this->value);
    }

    /**
     * Get the translation key explaining why authentication is blocked.
     */
    public function blockedMessageKey(): string
    {
        return match ($this) {
            self::Banned => 'auth.account_banned',
            self::Suspended => 'auth.account_suspended',
            self::Inactive => 'auth.account_inactive',
            default => 'auth.failed',
        };
    }
}
