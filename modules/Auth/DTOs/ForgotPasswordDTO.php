<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var string $email */
        $email = $request->validated('email');

        return new self(
            email: $email
        );
    }
}
