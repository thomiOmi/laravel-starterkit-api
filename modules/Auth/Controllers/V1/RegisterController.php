<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\RegisterRequest;

/**
 * @tags Authentication
 */
class RegisterController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected RegisterAction $registerAction
    ) {}

    /**
     * Handle registration request.
     *
     * @param  RegisterRequest  $request  The registration request.
     * @return JsonResponse The JSON response containing created user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registerAction->execute($request->validated());
        $user->sendEmailVerificationNotification();

        return $this->successResponse(
            ['user' => $user],
            __('auth.registered'),
            201
        );
    }
}
