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

        if (! $user) {
            return false;
        }

        $action = $this->string('action')->toString();
        $resource = $this->getResourceType();

        return match ($action) {
            'delete' => $user->can($resource.'.delete'),
            'restore' => $user->can($resource.'.edit'),
            default => false,
        };
    }

    /**
     * Get the resource type from the route.
     */
    protected function getResourceType(): string
    {
        $path = $this->path();

        if (str_contains($path, 'users')) {
            return 'user';
        }

        if (str_contains($path, 'roles')) {
            return 'role';
        }

        return 'unknown';
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
}
