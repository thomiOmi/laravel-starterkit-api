<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk Action Request
 *
 * Validate bulk action requests for any module.
 */
#[BodyParameter(name: 'action', description: 'The bulk action to perform.', required: true, example: 'delete')]
#[BodyParameter(name: 'ids', description: 'An array of resource IDs (ULID) to perform the action on.', required: true, example: ['01hpv4n8f8xrd2m8q0e4x8j9v1', '01hpv4n8f8xrd2m8q0e4x8j9v2'])]
class BulkActionRequest extends FormRequest
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

        $routeName = (string) $this->route()?->getName();

        $inferredAction = null;
        if (str_contains($routeName, 'delete')) {
            $inferredAction = 'delete';
        } elseif (str_contains($routeName, 'restore')) {
            $inferredAction = 'restore';
        }

        if ($inferredAction === null) {
            return false;
        }

        $bodyAction = $this->input('action');
        if ($bodyAction !== null && $bodyAction !== $inferredAction) {
            return false;
        }

        $permissions = [
            'users' => [
                'delete' => 'user.delete',
                'restore' => 'user.edit',
            ],
            'roles' => [
                'delete' => 'role.delete',
                'restore' => 'role.edit',
            ],
        ];

        foreach ($permissions as $module => $actions) {
            if (str_contains($routeName, $module.'.')) {
                return $user->can($actions[$inferredAction]);
            }
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['required', 'string', 'ulid'],
            'action' => ['nullable', 'string', 'in:delete,restore'],
        ];
    }
}
