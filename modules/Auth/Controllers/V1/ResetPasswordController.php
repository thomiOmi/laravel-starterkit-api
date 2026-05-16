<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\Auth\DTOs\ResetPasswordDTO;
use Modules\Auth\Requests\ResetPasswordRequest;

/**
 * @tags Auth
 */
class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $dto = ResetPasswordDTO::fromRequest($request);
        $status = $action->execute($dto);

        return new JsonDataResponse(data: null, message: __($status));
    }
}
