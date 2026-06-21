<?php

declare(strict_types=1);

namespace Modules\Role\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Modules\Role\Models\Role;
use Modules\Role\Payloads\V1\RolePayload;

/**
 * Role Request
 *
 * The request parameters for creating or updating a role.
 */
#[BodyParameter(name: 'name', description: 'The unique name of the role.', required: true, example: 'editor')]
#[BodyParameter(name: 'description', description: 'A brief description of the role purpose.', required: false, example: 'Can edit and publish content')]
#[BodyParameter(name: 'permissions', description: 'An array of permission names to assign to the role.', required: false, example: ['user.view', 'user.create'])]
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

        return $this->isMethod('POST')
            ? $user->can('role.create')
            : $user->can('role.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Unique>> The validation rules.
     */
    public function rules(): array
    {
        $roleId = $this->getRoleId();

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
     * Get the ID of the role being updated.
     */
    public function getRoleId(): string
    {
        $role = $this->route('role');

        return $role instanceof Role ? (string) $role->id : (string) $role;
    }

    /**
     * Get the payload for the request.
     */
    public function payload(): RolePayload
    {
        return RolePayload::fromRequest($this);
    }
}
