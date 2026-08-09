<?php

declare(strict_types=1);

namespace Modules\Media\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Models\User;
use Modules\Media\Models\Media;
use Modules\Media\Resources\MediaResource;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class MediaShowController extends Controller
{
    /**
     * Display the specified media.
     *
     * @return SuccessResponse<MediaResource>
     */
    public function __invoke(#[CurrentUser] User $currentUser, Media $media): SuccessResponse
    {
        if (! $currentUser->can('view', $media)) {
            throw new AccessDeniedHttpException(
                __('general.action_forbidden')
            );
        }

        return new SuccessResponse(
            data: new MediaResource($media),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Media']),
        );
    }
}
