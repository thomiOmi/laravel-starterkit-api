<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Carbon;

trait HasDateFormatting
{
    /**
     * Format the given date to a standardized string format.
     */
    protected function formatDate(\DateTimeInterface|string|null $date): ?string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date?->format('Y-m-d H:i:s');
    }
}
