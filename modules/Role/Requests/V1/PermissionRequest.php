<?php

declare(strict_types=1);

namespace Modules\Role\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Role\Payloads\V1\PermissionPayload;

#[BodyParameter(name: 'name', description: 'The unique permission name (e.g., "post.create").', required: true, example: 'post.create')]
#[BodyParameter(name: 'guard_name', description: 'The guard name for the permission.', required: false, example: 'web')]
final class PermissionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $permissionId = $this->route('permission');

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

    public function payload(): PermissionPayload
    {
        return PermissionPayload::fromRequest($this);
    }
}
