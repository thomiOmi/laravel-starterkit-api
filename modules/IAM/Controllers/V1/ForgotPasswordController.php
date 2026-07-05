<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\ForgotPasswordAction;
use Modules\IAM\Requests\V1\ForgotPasswordRequest;

final readonly class ForgotPasswordController
{
    public function __construct(
        private ForgotPasswordAction $forgotPasswordAction
    ) {}

    /**
     * @return SuccessResponse<null>
     */
    public function __invoke(ForgotPasswordRequest $request): SuccessResponse
    {
        $this->forgotPasswordAction->handle($request->string('email')->toString());

        return new SuccessResponse(
            title: 'OK',
            detail: __('passwords.sent'),
        );
    }
}
