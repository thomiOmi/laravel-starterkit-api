<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\Auth\Requests\V1\ForgotPasswordRequest;

final readonly class ForgotPasswordController
{
    public function __construct(
        private ForgotPasswordAction $forgotPasswordAction
    ) {}

    public function __invoke(ForgotPasswordRequest $request): SuccessResponse
    {
        $this->forgotPasswordAction->handle($request->string('email')->toString());

        return new SuccessResponse(
            'OK',
            __('passwords.sent'),
        );
    }
}
