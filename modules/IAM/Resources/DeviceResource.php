<?php

declare(strict_types=1);

namespace Modules\IAM\Resources;

use App\Concerns\FormatDates;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\IAM\Models\User;

/**
 * @property-read PersonalAccessToken $resource
 *
 * @mixin PersonalAccessToken
 */
class DeviceResource extends JsonResource
{
    use FormatDates;

    /**
     * @return array{id: string, name: string, ip_address: ?string, user_agent: ?string, last_used_at: ?string, created_at: ?string, is_current: bool}
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $userCurrentToken */
        $userCurrentToken = $user->currentAccessToken();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'ip_address' => $this->resource->ip_address,
            'user_agent' => $this->resource->user_agent,
            'last_used_at' => $this->formatDate($this->resource->last_used_at),
            'created_at' => $this->formatDate($this->resource->created_at),
            'is_current' => (string) $userCurrentToken->id === (string) $this->resource->id,
        ];
    }
}
