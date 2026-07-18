<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Modules\IAM\Payloads\V1\RolePayload;
use Spatie\Permission\Contracts\Role;

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
        $isAuthorized = $this->isMethod('POST')
            ? $user->can(PermissionEnum::RoleCreate->value)
            : $user->can(PermissionEnum::RoleEdit->value);

        if (! $isAuthorized) {
            return false;
        }

        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        $roleId = $this->route('role');
        if (is_string($roleId) || $roleId instanceof Role) {
            if ($roleId instanceof Role) {
                $role = $roleId;
            } else {
                /** @var class-string<Model> $roleModel */
                $roleModel = (string) config('permission.models.role', 'Modules\IAM\Models\Role');
                /** @var Role $role */
                $role = $roleModel::query()->findOrFail($roleId);
            }

            if ($role->name === RoleEnum::SuperAdmin->value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Unique>> The validation rules.
     */
    public function rules(): array
    {
        $roleId = $this->route('role');
        $roleIdString = $roleId instanceof Role ? (string) $roleId->id : (is_string($roleId) ? $roleId : null);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleIdString),
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
