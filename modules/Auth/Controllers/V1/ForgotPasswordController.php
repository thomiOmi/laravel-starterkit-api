<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\Auth\DTOs\ForgotPasswordDTO;
use Modules\Auth\Requests\ForgotPasswordRequest;

class ForgotPasswordController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected ForgotPasswordAction $forgotPasswordAction
    ) {}

    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $dto = ForgotPasswordDTO::fromRequest($request);
            $this->forgotPasswordAction->execute($dto);

            return $this->successResponse(null, __('passwords.sent'));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}
