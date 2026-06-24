<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRolesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Authenticatable|null $user */
        $user = $this->user();

        return $user?->can('user.edit') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
        $user = $this->route('user');

        if (is_object($user) && method_exists($user, 'getKey')) {
            return (string) $user->getKey();
        }

        return (string) $user;
    }
}
