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
     * @param  array<int, string>  $permissions  The list of permissions.
     * @param  string|null  $description  The role description.
     */
    public function __construct(
        public string $name,
        public array $permissions = [],
        public ?string $description = null
    ) {}

    /**
     * Create a RoleDTO instance from a request.
     *
     * @param  FormRequest  $request  The incoming request.
     * @return self The created DTO instance.
     */
    public static function fromRequest(FormRequest $request): self
    {
        /** @var string $name */
        $name = $request->validated('name');

        /** @var array<int, string> $permissions */
        $permissions = $request->validated('permissions', []);

        /** @var string|null $description */
        $description = $request->validated('description');

        return new self(
            name: $name,
            permissions: $permissions,
            description: $description
        );
    }
}
