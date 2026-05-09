<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for email verification.
 */
readonly class VerifyEmailDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param  string|int  $id  The user ID.
     * @param  string  $hash  The verification hash.
     */
    public function __construct(
        public string|int $id,
        public string $hash
    ) {}

    /**
     * Create a new DTO instance from a request.
     *
     * @param  Request  $request  The incoming request.
     * @return self The created DTO instance.
     */
    public static function fromRequest(Request $request): self
    {
        /** @var string|int $id */
        $id = $request->route('id', '');
        /** @var string $hash */
        $hash = $request->route('hash', '');

        return new self(
            id: $id,
            hash: $hash
        );
    }
}
