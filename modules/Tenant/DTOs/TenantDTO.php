<?php

declare(strict_types=1);

namespace Modules\Tenant\DTOs;

use Illuminate\Http\Request;

readonly class TenantDTO
{
    public function __construct(
        public string $id,
        public string $domain,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id: $request->string('id')->toString(),
            domain: $request->string('domain')->toString(),
        );
    }
}
