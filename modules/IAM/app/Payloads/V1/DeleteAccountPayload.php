<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Http\Requests\V1\DeleteAccountRequest;

final readonly class DeleteAccountPayload
{
    public function __construct(
        public string $password,
    ) {}

    public static function fromRequest(DeleteAccountRequest $request): self
    {
        return new self(
            password: $request->safe()->string('password')->toString(),
        );
    }

    /**
     * @return array{password: string}
     */
    public function toArray(): array
    {
        return [
            'password' => $this->password,
        ];
    }
}
