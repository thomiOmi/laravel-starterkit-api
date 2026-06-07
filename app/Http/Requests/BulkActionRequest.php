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

        $resource = $this->getResourceName();
        $action = $this->string('action')->toString();

        // Map 'restore' to 'edit' or 'delete' based on policy names if needed,
        // but here we use the requested action directly or mapped to permission.
        $permissionAction = match ($action) {
            'restore' => 'edit',
            'delete' => 'delete',
            default => $action,
        };

        return $user->can($resource.'.'.$permissionAction);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
            'action' => ['required', 'string', 'in:delete,restore'],
        ];
    }

    /**
     * Infer resource name from route or request.
     */
    protected function getResourceName(): string
    {
        // Try to get from route name e.g. api.v1.users.bulk -> user
        $routeName = $this->route()?->getName() ?? '';
        if (str_contains($routeName, 'users')) {
            return 'user';
        }
        if (str_contains($routeName, 'roles')) {
            return 'role';
        }

        return 'resource';
    }
}
