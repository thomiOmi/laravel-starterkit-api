<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Carbon;

trait FormatDate
{
    protected function formatDate(\DateTimeInterface|string|null $date): ?string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date?->format('Y-m-d H:i:s');
    }
}
