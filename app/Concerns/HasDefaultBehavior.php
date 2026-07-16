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
    use FormatDate, HasUlids;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $this->formatDate($date) ?? $date->format('Y-m-d H:i:s');
    }
}
