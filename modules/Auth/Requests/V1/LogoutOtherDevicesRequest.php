<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

#[BodyParameter(name: 'current_password', description: 'Current password for confirmation.', required: true, example: 'current-password123')]
final class LogoutOtherDevicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }
}
