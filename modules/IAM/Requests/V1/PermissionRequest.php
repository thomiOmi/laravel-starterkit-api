<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\IAM\Payloads\V1\PermissionPayload;

final class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->isMethod('POST')
            ? ($this->user()?->can(PermissionEnum::PermissionCreate->value) ?? false)
            : ($this->user()?->can(PermissionEnum::PermissionEdit->value) ?? false);
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
            'guard_name' => ['nullable', 'string', Rule::in(array_keys(config()->array('auth.guards', [])))],
        ];
    }

    public function payload(): PermissionPayload
    {
        return PermissionPayload::fromRequest($this);
    }
}
