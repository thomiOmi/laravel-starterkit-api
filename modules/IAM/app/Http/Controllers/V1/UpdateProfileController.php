<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\UpdateProfileAction;
use Modules\IAM\Http\Requests\V1\UpdateProfileRequest;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UpdateProfilePayload;

final readonly class UpdateProfileController extends Controller
{
    public function __construct(
        private UpdateProfileAction $updateProfile
    ) {}

    /**
     * @return SuccessResponse<array{user: UserResource, verification_required: bool}>
     */
    public function __invoke(UpdateProfileRequest $request, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $result = $this->updateProfile->handle($currentUser, UpdateProfilePayload::fromRequest($request));

        $result['user']->load(['roles:id,name,guard_name', 'permissions:id,name']);

        return new SuccessResponse(
            data: [
                'user' => new UserResource($result['user']),
                'verification_required' => $result['verification_required'],
            ],
            title: 'OK',
            detail: $result['verification_required']
                ? __('auth.email_change_verify')
                : __('auth.profile_updated'),
        );
    }
}
