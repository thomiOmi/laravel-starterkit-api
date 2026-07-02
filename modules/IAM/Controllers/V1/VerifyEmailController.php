<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

final readonly class VerifyEmailController
{
    public function __invoke(EmailVerificationRequest $request): SuccessResponse
    {
        $request->fulfill();

        return new SuccessResponse(
            'OK',
            __('auth.verified'),
            ['verified' => true],
        );
    }
}
