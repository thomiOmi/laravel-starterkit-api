<?php

declare(strict_types=1);

use Modules\IAM\Http\Requests\V1\ForgotPasswordRequest;
use Modules\IAM\Http\Requests\V1\LoginRequest;
use Modules\IAM\Http\Requests\V1\ResetPasswordRequest;

covers([LoginRequest::class, ForgotPasswordRequest::class, ResetPasswordRequest::class]);

describe('non-mutating email rules', function (): void {
    it('keeps the RFC and spoof-check policy out of login requests', function (): void {
        expect((new LoginRequest)->rules()['email'])
            ->toBe(['bail', 'required', 'string', 'max:255', 'email']);
    });

    it('keeps the RFC and spoof-check policy out of forgot password requests', function (): void {
        expect((new ForgotPasswordRequest)->rules()['email'])
            ->toBe(['bail', 'required', 'string', 'max:255', 'email']);
    });

    it('keeps the RFC and spoof-check policy out of reset password requests', function (): void {
        expect((new ResetPasswordRequest)->rules()['email'])
            ->toBe(['bail', 'required', 'string', 'max:255', 'email']);
    });
});
