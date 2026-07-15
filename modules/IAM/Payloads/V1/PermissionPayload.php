<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Requests\V1\PermissionRequest;

final readonly class PermissionPayload
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(PermissionRequest $request): self
    {
        return new self(
            name: $request->safe()->string('name')->trim()->toString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => 'sanctum',
        ];
    }
}
