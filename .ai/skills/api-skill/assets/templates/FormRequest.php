<?php

declare(strict_types=1);

namespace Modules\{Module}\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\{Module}\Payloads\V1\{Action}{Resource}Payload;

/**
 * Class {Action}{Resource}Request
 *
 * @package Modules\{Module}\Requests\V1
 */
final class {Action}{Resource}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Example with Policy: return $this->user()->can('create', Resource::class);
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Rules here
        ];
    }

    /**
     * Get the request payload.
     *
     * @return {Action}{Resource}Payload
     */
    public function payload(): {Action}{Resource}Payload
    {
        return {Action}{Resource}Payload::from($this->validated());
    }
}
