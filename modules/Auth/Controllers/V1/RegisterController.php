<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Auth
 */
final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    public function __invoke(RegisterRequest $request): JsonDataResponse
    {
        $user = $this->registerAction->handle($request->payload());

        return new JsonDataResponse(
            data: new UserResource($user),
            status: Response::HTTP_CREATED,
            message: __('auth.registered')
        );
    }
}
