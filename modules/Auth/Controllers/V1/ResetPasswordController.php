<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\Auth\DTOs\ResetPasswordDTO;
use Modules\Auth\Requests\ResetPasswordRequest;

/**
 * @tags Authentication
 */
class ResetPasswordController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ResetPasswordAction $resetPasswordAction
    ) {}

    /**
     * Reset user password.
     *
     * @param  ResetPasswordRequest  $request  The reset password request.
     * @return JsonResponse The JSON response.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $dto = ResetPasswordDTO::fromRequest($request);
            $this->resetPasswordAction->execute($dto);

            return $this->successResponse(null, __('passwords.reset'));
        } catch (ValidationException $e) {
            /** @var array<string, mixed> $errors */
            $errors = $e->errors();

            return $this->errorResponse($e->getMessage(), 422, $errors);
        }
    }
}
