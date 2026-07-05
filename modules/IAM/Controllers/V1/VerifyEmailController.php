<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

final readonly class VerifyEmailController
{
    /**
     * @return SuccessResponse<array{verified: bool}>
     *
     * @throws AuthenticationException Invalid or expired verification link.
     */
    public function __invoke(EmailVerificationRequest $request): SuccessResponse
    {
        $request->fulfill();

        return new SuccessResponse(
            data: ['verified' => true],
            title: 'OK',
            detail: __('auth.verified'),
        );
    }
}
