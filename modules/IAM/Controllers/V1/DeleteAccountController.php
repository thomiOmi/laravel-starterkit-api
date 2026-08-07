<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\DeleteAccountAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\DeleteAccountRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteAccountController
{
    public function __construct(
        private DeleteAccountAction $deleteAccount,
    ) {}

    /**
     * Delete the authenticated user's account (self-service).
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(DeleteAccountRequest $request, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $this->deleteAccount->handle($currentUser, $request->payload());

        return new SuccessResponse(
            data: null,
            title: __('general.resource_deleted', ['resource' => 'Account']),
            detail: __('general.resource_deleted', ['resource' => 'Account']),
            status: Response::HTTP_OK,
        );
    }
}
