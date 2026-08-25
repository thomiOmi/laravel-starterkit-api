<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Contracts\MediaUrlResolver;
use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Modules\IAM\Models\User;
use Modules\Media\Http\Requests\V1\MediaShowRequest;
use Modules\Media\Http\Resources\MediaResource;
use Modules\Media\Models\Media;

final readonly class MediaShowController extends Controller
{
    public function __construct(
        private MediaUrlResolver $mediaUrls,
    ) {}

    /**
     * Display the media item.
     *
     * Passing ?expires=<minutes> swaps the url for a temporary signed
     * streaming link valid for that long; private media without it keeps
     * resolving to null.
     *
     * @return SuccessResponse<MediaResource>
     */
    public function __invoke(MediaShowRequest $showRequest, #[CurrentUser] User $currentUser, Media $media): SuccessResponse
    {
        Gate::authorize('view', $media);

        $expires = $showRequest->expiresMinutes();
        $url = $expires !== null
            ? $this->mediaUrls->signed($media->id, $expires)
            : null;

        return new SuccessResponse(
            data: new MediaResource($media, $url),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Media']),
        );
    }
}
