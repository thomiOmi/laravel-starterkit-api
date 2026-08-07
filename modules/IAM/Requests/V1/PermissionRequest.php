<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use Illuminate\Database\Eloquent\Model;
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
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        /** @var Identity $user */
        if ($this->isMethod('POST')) {
            /** @var class-string<Model> $permissionModel */
            $permissionModel = (string) config('permission.models.permission');

            return $user->can('create', $permissionModel);
        }

        $permissionId = $this->route('permission');

        if (is_string($permissionId)) {
            /** @var class-string<Model> $permissionModel */
            $permissionModel = (string) config('permission.models.permission');
            /** @var Model $permission */
            $permission = $permissionModel::query()->findOrFail($permissionId);

            return $user->can('update', $permission);
        }

        if ($permissionId instanceof Model) {
            return $user->can('update', $permissionId);
        }

        return $user->can(PermissionEnum::PermissionEdit->value);
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
        ];
    }

    public function payload(): PermissionPayload
    {
        return PermissionPayload::fromRequest($this);
    }
}
