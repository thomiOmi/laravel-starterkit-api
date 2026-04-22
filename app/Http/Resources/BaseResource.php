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
    /** @var string|null */
    public static $wrap = 'data';

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'status' => 'Success',
            'meta' => [
                'api_version' => config('app.api_version', '1.0.0'),
            ],
        ];
    }

    /**
     * Format a date consistently for API responses.
     */
    protected function formatDate(\DateTimeInterface|string|null $date): ?string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date?->format('Y-m-d H:i:s');
    }
}
