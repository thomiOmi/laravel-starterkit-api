<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\ResetPasswordAction;
use Modules\IAM\Requests\V1\ResetPasswordRequest;

final readonly class ResetPasswordController
{
    public function __construct(
        private ResetPasswordAction $resetPasswordAction
    ) {}

    /**
     * @return SuccessResponse<null>
     *
     * @throws ValidationException The submitted data failed validation rules.
     */
    public function __invoke(ResetPasswordRequest $request): SuccessResponse
    {
        $this->resetPasswordAction->handle([
            'token' => $request->string('token')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'password_confirmation' => $request->string('password_confirmation')->toString(),
        ]);

        return new SuccessResponse(
            title: 'OK',
            detail: __('passwords.reset'),
        );
    }
}
