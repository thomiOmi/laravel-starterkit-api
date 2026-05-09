<?php

declare(strict_types=1);

namespace Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property mixed $id
 * @property string $name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 */
class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Modules\User\Models\User $user */
        $user = $request->user();

        /** @var \Laravel\Sanctum\PersonalAccessToken $currentToken */
        $userCurrentToken = $user->currentAccessToken();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'is_current' => (string) $userCurrentToken->id === (string) $this->id,
        ];
    }
}
