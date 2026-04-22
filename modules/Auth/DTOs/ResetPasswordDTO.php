<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Http\Request;

readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
        public ?string $password_confirmation = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            token: $request->validated('token'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            password_confirmation: $request->validated('password_confirmation')
        );
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ];
    }
}
