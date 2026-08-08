<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Trait providing default behaviors for models.
 *
 * - ID strategy: ULID (standard)
 * - Date serialization: Y-m-d H:i:s
 */
trait HasDefaultBehavior
{
    use HasUlids;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
