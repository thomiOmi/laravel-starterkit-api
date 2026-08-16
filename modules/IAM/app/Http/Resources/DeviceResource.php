<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Resources;

use App\Concerns\FormatDate;
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
    use FormatDate;

    /**
     * @return array{id: string, name: string, ip_address: ?string, user_agent: ?string, last_used_at: ?string, created_at: ?string, is_current: bool}
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $userCurrentToken */
        $userCurrentToken = $user->currentAccessToken();

        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'ip_address' => array_key_exists('ip_address', $attributes)
                ? $this->resource->ip_address
                : null,
            'user_agent' => array_key_exists('user_agent', $attributes)
                ? $this->resource->user_agent
                : null,
            'last_used_at' => array_key_exists('last_used_at', $attributes)
                ? $this->formatDate($this->resource->last_used_at)
                : null,
            'created_at' => array_key_exists('created_at', $attributes)
                ? $this->formatDate($this->resource->created_at)
                : null,
            'is_current' => $userCurrentToken->getKey() === $this->resource->getKey(),
        ];
    }
}
