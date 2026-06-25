<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

    /**
     * Get the user ID from the route.
     */
    public function getUserId(): string
    {
        $id = $this->route('user');

        return $id instanceof Model ? (string) $id->getKey() : (string) $id;
    }
}
