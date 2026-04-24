<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reset Password Request
 *
 * Reset the user's password using the token from the reset email.
 */
#[BodyParameter(name: 'token', description: 'The password reset token from the email link.', required: true, example: 'a1b2c3d4e5f6...')]
#[BodyParameter(name: 'email', description: 'The email address of the account.', required: true, example: 'user@example.com')]
#[BodyParameter(name: 'password', description: 'The new password (min 8 characters).', required: true, example: 'newpassword123')]
#[BodyParameter(name: 'password_confirmation', description: 'Confirm the new password, must match password.', required: true, example: 'newpassword123')]
class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
