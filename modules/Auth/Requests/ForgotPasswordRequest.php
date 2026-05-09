<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Forgot Password Request
 *
 * Send a password reset link to the user's email address.
 */
#[BodyParameter(name: 'email', description: 'The email address of the account to reset.', required: true, example: 'user@example.com')]
class ForgotPasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
