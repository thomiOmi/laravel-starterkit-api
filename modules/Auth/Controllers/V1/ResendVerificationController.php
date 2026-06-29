<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ResendVerificationAction;
use Modules\User\Models\User;

final readonly class ResendVerificationController
{
    public function __construct(
        private ResendVerificationAction $resendVerificationAction
    ) {}

    public function __invoke(Request $request): SuccessResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        $message = $this->resendVerificationAction->handle($currentUser);

        return new SuccessResponse('OK', $message);
    }
}
