<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
        public ?string $password_confirmation = null
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var string $token */
        $token = $request->validated('token');

        /** @var string $email */
        $email = $request->validated('email');

        /** @var string $password */
        $password = $request->validated('password');

        /** @var string|null $passwordConfirmation */
        $passwordConfirmation = $request->validated('password_confirmation');

        return new self(
            token: $token,
            email: $email,
            password: $password,
            password_confirmation: $passwordConfirmation
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
