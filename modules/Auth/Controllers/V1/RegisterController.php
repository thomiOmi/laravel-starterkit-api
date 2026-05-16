<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\RegisterRequest;
use Modules\Auth\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Auth
 */
class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $result = $action->execute($request->all());

        return new JsonDataResponse(
            data: [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            status: Response::HTTP_CREATED,
            message: __('auth.registered')
        );
    }
}
