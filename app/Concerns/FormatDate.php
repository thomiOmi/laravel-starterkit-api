<?php

declare(strict_types=1);

namespace App\Concerns;

use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use Illuminate\Support\Carbon;

trait FormatDate
{
    protected function formatDate(DateTimeInterface|string|null $date): ?string
    {
        if (is_string($date)) {
            if ($date === '') {
                return null;
            }

            try {
                $date = Carbon::parse($date);
            } catch (InvalidFormatException) {
                return null;
            }
        }

        return $date !== null ? $this->formatDateTime($date) : null;
    }

    protected function formatDateTime(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
