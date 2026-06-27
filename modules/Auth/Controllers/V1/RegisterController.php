<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Events\Registered;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    public function __invoke(RegisterRequest $request): SuccessResponse
    {
        $isFrontend = EnsureFrontendRequestsAreStateful::fromFrontend($request);

        $result = $this->registerAction->handle(
            $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            stateful: $isFrontend,
        );

        event(new Registered($result['user']));

        $data = [
            'user' => new UserResource($result['user']),
        ];

        if ($result['access_token'] !== null) {
            $data['access_token'] = $result['access_token'];
            $data['token_type'] = $result['token_type'];
        }

        return new SuccessResponse(
            'Created',
            __('auth.registered'),
            $data,
            Response::HTTP_CREATED,
        );
    }
}
