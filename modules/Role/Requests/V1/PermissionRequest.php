<?php

declare(strict_types=1);

namespace Modules\Role\Requests\V1;

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
        $permissionId = $this->route('permission');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permissionId),
            ],
            'guard_name' => ['nullable', 'string', Rule::in(array_keys((array) config('auth.guards', [])))],
        ];
    }

    public function payload(): PermissionPayload
    {
        return PermissionPayload::fromRequest($this);
    }
}
