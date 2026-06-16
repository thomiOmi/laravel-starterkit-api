<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('Auth')]
final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    #[Endpoint(operationId: 'register', title: 'Register')]
    #[ScrambleResponse(status: 201, description: 'User registered successfully', examples: ['status' => 201, 'message' => 'Registration successful.', 'data' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com']])]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->registerAction->handle($request->payload());

        return new JsonResponse(
            [
                'status' => Response::HTTP_CREATED,
                'message' => __('auth.registered'),
                'data' => new UserResource($user),
            ],
            Response::HTTP_CREATED,
        );
    }
}
