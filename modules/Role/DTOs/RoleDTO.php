<?php

declare(strict_types=1);

namespace Modules\Role\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class RoleDTO
{
    /**
     * Create a new RoleDTO instance.
     *
     * @param  string  $name  The role name.
     * @param  array  $permissions  The list of permissions.
     * @param  string|null  $description  The role description.
     */
    public function __construct(
        public string $name,
        public array $permissions = [],
        public ?string $description = null
    ) {}

    /**
     * Create a RoleDTO instance from a request.
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            permissions: $request->validated('permissions', []),
            description: $request->validated('description')
        );
    }
}
