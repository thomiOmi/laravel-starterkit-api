<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Modules\IAM\Models\User;
use Modules\Media\Http\Resources\MediaResource;
use Modules\Media\Models\Media;

final readonly class MediaShowController extends Controller
{
    /**
     * Display the media item.
     *
     * @return SuccessResponse<MediaResource>
     */
    public function __invoke(#[CurrentUser] User $currentUser, Media $media): SuccessResponse
    {
        Gate::authorize('view', $media);

        return new SuccessResponse(
            data: new MediaResource($media),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Media']),
        );
    }
}
