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
#[BodyParameter(name: 'action', description: 'The bulk action to perform.', required: false, example: 'delete')]
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

        if (str_contains($routeName, '.users.')) {
            if (str_contains($routeName, '.delete')) {
                return $user->can('user.delete');
            }

            if (str_contains($routeName, '.restore')) {
                return $user->can('user.edit');
            }
        }

        if (str_contains($routeName, '.roles.')) {
            if (str_contains($routeName, '.delete')) {
                return $user->can('role.delete');
            }

            if (str_contains($routeName, '.restore')) {
                return $user->can('role.edit');
            }

            // Fallback for legacy single bulk route
            $action = $this->string('action')->toString();

            return match ($action) {
                'delete' => $user->can('role.delete'),
                'restore' => $user->can('role.edit'),
                default => false,
            };
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
            'action' => ['sometimes', 'string', 'in:delete,restore'],
        ];
    }
}
