<?php

declare(strict_types=1);

namespace Modules\Media\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Models\User;
use Modules\Media\Actions\DeleteMediaAction;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class MediaDeleteController extends Controller
{
    public function __construct(
        private DeleteMediaAction $deleteMedia,
    ) {}

    /**
     * Remove the specified media from storage.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(#[CurrentUser] User $currentUser, Media $media): SuccessResponse
    {
        if (! $currentUser->can('delete', $media)) {
            throw new AccessDeniedHttpException(
                __('general.action_forbidden')
            );
        }

        if ($this->deleteMedia->handle($media)) {
            return new SuccessResponse(
                data: null,
                title: __('general.resource_deleted', ['resource' => 'Media']),
                detail: __('general.resource_deleted', ['resource' => 'Media']),
                status: Response::HTTP_OK,
            );
        }

        throw new AccessDeniedHttpException(
            __('general.resource_delete_error', ['resource' => 'Media'])
        );
    }
}
