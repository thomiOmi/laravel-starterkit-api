<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\IAM\Payloads\V1\DeleteAccountPayload;

final class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            /**
             * The current password used to confirm account deletion.
             *
             * @example current-password-123
             */
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function payload(): DeleteAccountPayload
    {
        return DeleteAccountPayload::fromRequest($this);
    }
}
