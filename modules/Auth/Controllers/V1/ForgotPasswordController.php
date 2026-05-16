<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\Auth\DTOs\ForgotPasswordDTO;
use Modules\Auth\Requests\ForgotPasswordRequest;

/**
 * @tags Auth
 */
class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $dto = ForgotPasswordDTO::fromRequest($request);
        $status = $action->execute($dto);

        return new JsonDataResponse(data: null, message: __($status));
    }
}
