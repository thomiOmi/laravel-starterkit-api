<?php

declare(strict_types=1);

namespace Modules\Role\Payloads\V1;

use Modules\Role\Requests\V1\RoleRequest;

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
        $permissions = (array) $request->input('permissions', []);

        return new self(
            name: trim($request->string('name')->toString()),
            permissions: $permissions,
            description: trim($request->string('description')->toString()) ?: null,
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
