<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Modules\IAM\Models\User;
use Modules\Media\Actions\DeleteMediaAction;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;

final readonly class MediaDeleteController extends Controller
{
    public function __construct(
        private DeleteMediaAction $deleteMedia
    ) {}

    /**
     * Delete a media item and its stored file.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(#[CurrentUser] User $currentUser, Media $media): SuccessResponse
    {
        Gate::authorize('delete', $media);

        $this->deleteMedia->handle($media);

        return new SuccessResponse(
            title: __('general.resource_deleted', ['resource' => 'Media']),
            detail: __('general.resource_deleted', ['resource' => 'Media']),
            status: Response::HTTP_OK,
        );
    }
}
