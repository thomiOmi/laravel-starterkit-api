<?php

declare(strict_types=1);

namespace Modules\Auth\Resources;

use App\Http\Resources\BaseResource;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;
use Modules\User\Models\User;

/**
 * @property-read PersonalAccessToken $resource
 *
 * @mixin PersonalAccessToken
 */
class DeviceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The incoming request.
     * @return array<string, mixed> The transformed resource array.
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
