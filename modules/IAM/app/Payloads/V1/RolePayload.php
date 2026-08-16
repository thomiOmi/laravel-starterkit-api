<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Http\Requests\V1\RoleRequest;

/**
 * Payload for Role data.
 */
final readonly class RolePayload
{
    /**
     * Create a new RolePayload instance.
     *
     * @param  string  $name  The role name.
     * @param  array<int, string>  $permissions  The list of permissions.
     * @param  string|null  $description  The role description.
     */
    public function __construct(
        public string $name,
        public array $permissions = [],
        public ?string $description = null
    ) {}

    /**
     * Create a RolePayload instance from a request.
     *
     * @param  RoleRequest  $request  The incoming request.
     * @return self The created Payload instance.
     */
    public static function fromRequest(RoleRequest $request): self
    {
        /** @var array<int, string> $permissions */
        $permissions = $request->safe()->input('permissions', []);

        return new self(
            name: $request->safe()->string('name')->trim()->toString(),
            permissions: $permissions,
            description: $request->safe()->string('description')->trim()->toString() ?: null,
        );
    }

    /**
     * Convert the payload to an array for Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
