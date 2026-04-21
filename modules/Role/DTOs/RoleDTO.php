<?php

declare(strict_types=1);

namespace Modules\Role\DTOs;

readonly class RoleDTO
{
    /**
     * Create a new RoleDTO instance.
     *
     * @param  string  $name  The role name.
     * @param  array  $permissions  The list of permissions.
     */
    public function __construct(
        public string $name,
        public array $permissions = []
    ) {}

    /**
     * Create a RoleDTO instance from a request.
     *
     * @param  mixed  $request
     */
    public static function fromRequest($request): self
    {
        return new self(
            name: $request->validated('name'),
            permissions: $request->validated('permissions', [])
        );
    }
}
