<?php

declare(strict_types=1);

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait providing default behaviors for models, including ULIDs and Soft Deletes.
 */
trait HasDefaultBehavior
{
    use HasUlids, SoftDeletes;

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
