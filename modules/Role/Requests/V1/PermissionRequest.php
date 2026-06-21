<?php

declare(strict_types=1);

namespace Modules\Role\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Role\Payloads\V1\PermissionPayload;
use Spatie\Permission\Models\Permission;

#[BodyParameter(name: 'name', description: 'The unique permission name (e.g., "post.create").', required: true, example: 'post.create')]
#[BodyParameter(name: 'guard_name', description: 'The guard name for the permission.', required: false, example: 'web')]
final class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $this->isMethod('POST')
            ? $user->can('permission.create')
            : $user->can('permission.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $permissionId = $this->getPermissionId();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permissionId),
            ],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the ID of the permission being updated.
     */
    public function getPermissionId(): string
    {
        $permission = $this->route('permission');

        return $permission instanceof Permission ? (string) $permission->id : (string) $permission;
    }

    public function payload(): PermissionPayload
    {
        return PermissionPayload::fromRequest($this);
    }
}
