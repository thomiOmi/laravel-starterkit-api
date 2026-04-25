<?php

declare(strict_types=1);

namespace Modules\ApiKey\Actions;

use Illuminate\Support\Str;
use Modules\ApiKey\DTOs\ApiKeyDTO;
use Modules\ApiKey\Models\ApiKey;

class CreateApiKeyAction
{
    /**
     * Execute the action to create a new API Key.
     *
     * @return array{api_key: ApiKey, plain_text_key: string}
     */
    public function execute(ApiKeyDTO $dto, string $userId): array
    {
        $plainKey = Str::random(40);
        $prefix = Str::substr($plainKey, 0, 8);
        $hashedKey = hash('sha256', $plainKey);

        $apiKey = ApiKey::create([
            'user_id' => $userId,
            'name' => $dto->name,
            'key' => $hashedKey,
            'secret_prefix' => $prefix,
            'abilities' => $dto->abilities ?? ['*'],
            'ip_whitelist' => $dto->ip_whitelist,
            'expires_at' => $dto->expires_at,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_text_key' => 'sk_live_'.$plainKey,
        ];
    }
}
