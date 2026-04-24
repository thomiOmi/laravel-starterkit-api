<?php

declare(strict_types=1);

namespace Modules\Role\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Role Request
 *
 * The request parameters for creating or updating a role.
 */
#[BodyParameter(name: 'name', description: 'The unique name of the role.', required: true, example: 'editor')]
#[BodyParameter(name: 'description', description: 'A brief description of the role purpose.', required: false, example: 'Can edit and publish content')]
#[BodyParameter(name: 'permissions', description: 'An array of permission names to assign to the role.', required: false, example: ['user.view', 'user.create'])]
class RoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
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
}
