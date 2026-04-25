<?php

declare(strict_types=1);

namespace Modules\ApiKey\DTOs;

use Illuminate\Http\Request;

readonly class ApiKeyDTO
{
    /**
     * ApiKeyDTO constructor.
     */
    public function __construct(
        public string $name,
        public ?array $abilities = ['*'],
        public ?array $ip_whitelist = null,
        public ?string $expires_at = null,
    ) {}

    /**
     * Create a DTO from a request.
     */
    public static function fromRequest(Request $request): static
    {
        return new static(
            name: $request->string('name')->toString(),
            abilities: $request->input('abilities', ['*']),
            ip_whitelist: $request->input('ip_whitelist'),
            expires_at: $request->input('expires_at'),
        );
    }
}
