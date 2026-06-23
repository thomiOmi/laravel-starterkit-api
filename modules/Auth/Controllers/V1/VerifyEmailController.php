<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyEmailController
{
    public function __construct(
        private VerifyEmailAction $verifyEmail,
    ) {}

    public function __invoke(string $id, string $hash): SuccessResponse|ProblemResponse
    {
        $user = $this->verifyEmail->handle($id, $hash);

        if (! $user instanceof User) {
            return new ProblemResponse(
                title: __('auth.not_found'),
                status: Response::HTTP_NOT_FOUND,
                detail: 'User not found.',
            );
        }

        return new SuccessResponse(
            'OK',
            __('auth.verified'),
            ['verified' => true],
        );
    }
}
