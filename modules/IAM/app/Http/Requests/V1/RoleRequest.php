<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Modules\IAM\Payloads\V1\RolePayload;

/**
 * Role Request
 *
 * The request parameters for creating or updating a role.
 */
final class RoleRequest extends FormRequest
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
            /** @var class-string<Model> $roleModel */
            $roleModel = (string) config('permission.models.role');

            return $user->can('create', $roleModel);
        }

        $roleId = $this->route('role');

        if (is_string($roleId)) {
            /** @var class-string<Model> $roleModel */
            $roleModel = (string) config('permission.models.role');
            /** @var Model $role */
            $role = $roleModel::query()->findOrFail($roleId);

            return $user->can('update', $role);
        }

        if ($roleId instanceof Model) {
            return $user->can('update', $roleId);
        }

        return $user->can(PermissionEnum::RoleEdit->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Unique>> The validation rules.
     */
    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the payload for the request.
     */
    public function payload(): RolePayload
    {
        return RolePayload::fromRequest($this);
    }
}
