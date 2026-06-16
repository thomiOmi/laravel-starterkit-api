<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

#[Group('Auth')]
final readonly class VerifyEmailController
{
    public function __construct(
        private VerifyEmailAction $verifyEmail,
    ) {}

    #[Endpoint(operationId: 'verifyEmail', title: 'Verify Email')]
    #[ScrambleResponse(status: 200, description: 'Email verified successfully', examples: ['status' => 200, 'message' => 'Email verified.', 'data' => ['verified' => true]])]
    public function __invoke(string $id, string $hash): JsonResponse|ProblemResponse
    {
        $user = $this->verifyEmail->handle($id, $hash);

        if (! $user instanceof User) {
            return new ProblemResponse(
                title: __('auth.not_found'),
                status: Response::HTTP_NOT_FOUND,
                detail: 'User not found.',
            );
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('auth.verified'),
                'data' => ['verified' => true],
            ],
            Response::HTTP_OK,
        );
    }
}
