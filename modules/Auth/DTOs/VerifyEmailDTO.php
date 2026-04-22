<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Http\Request;

class VerifyEmailDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public string|int $id,
        public string $hash
    ) {}

    /**
     * Create a new DTO instance from a request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            id: $request->route('id'),
            hash: $request->route('hash')
        );
    }
}
