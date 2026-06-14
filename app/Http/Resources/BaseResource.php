<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Base Resource class for all API resources.
 */
class BaseResource extends JsonResource
{
    // public static $wrap = 'data';

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'api_version' => config('apiroute.default_version', 'V1'),
            ],
        ];
    }

    /**
     * Format a date time consistently for API responses.
     *
     * @param  \DateTimeInterface|string|null  $date  The date time to format.
     * @return string|null The formatted date "YYYY-MM-DD HH:mm:ss" or null if the input is null.
     */
    protected function formatDate(\DateTimeInterface|string|null $date): ?string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date?->format('Y-m-d H:i:s');
    }
}
