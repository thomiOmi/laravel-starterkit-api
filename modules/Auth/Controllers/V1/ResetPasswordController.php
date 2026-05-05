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

class ResetPasswordController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected ResetPasswordAction $resetPasswordAction
    ) {}

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $dto = ResetPasswordDTO::fromRequest($request);
            $this->resetPasswordAction->execute($dto);

            return $this->successResponse(null, __('passwords.reset'));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}
