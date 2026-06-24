<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the user ID from the route.
     */
    public function getUserId(): string
    {
        $userId = $this->route('user');

        return is_string($userId) || is_int($userId) ? (string) $userId : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }
}
