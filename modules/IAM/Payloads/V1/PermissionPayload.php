<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Requests\V1\PermissionRequest;

final readonly class PermissionPayload
{
    public function __construct(
        public string $name,
        public string $guardName = 'web',
    ) {}

    public static function fromRequest(PermissionRequest $request): self
    {
        return new self(
            name: trim($request->string('name')->toString()),
            guardName: trim($request->string('guard_name', 'web')->toString()) ?: 'web',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => $this->guardName,
        ];
    }
}
