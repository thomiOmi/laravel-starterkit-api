<?php

declare(strict_types=1);

namespace Modules\Role\Requests\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Role\Payloads\V1\PermissionPayload;

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
     * Get the permission ID from the route.
     */
    public function getPermissionId(): string
    {
        $permissionId = $this->route('permission');

        return $permissionId instanceof Model
            ? (string) $permissionId->getKey()
            : (string) $permissionId;
    }

    public function payload(): PermissionPayload
    {
        return PermissionPayload::fromRequest($this);
    }
}
