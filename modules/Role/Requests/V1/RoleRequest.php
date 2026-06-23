<?php

declare(strict_types=1);

namespace Modules\Role\Requests\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Modules\Role\Payloads\V1\RolePayload;

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
     * Get the role ID from the route.
     */
    public function getRoleId(): string
    {
        $roleId = $this->route('role');

        if ($roleId instanceof Model) {
            /** @var string|int $key */
            $key = $roleId->getKey();

            return (string) $key;
        }

        return (string) ($roleId ?? '');
    }

    /**
     * Get the payload for the request.
     */
    public function payload(): RolePayload
    {
        return RolePayload::fromRequest($this);
    }
}
