<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Carbon;

/**
 * Trait for standardized date formatting in API resources.
 */
trait HasDateFormatting
{
    /**
     * Format a date for API response.
     */
    protected function formatDate(\DateTimeInterface|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format('Y-m-d H:i:s');
    }
}
