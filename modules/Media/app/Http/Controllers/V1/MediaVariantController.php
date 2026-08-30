<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Contracts\Identity;
use App\Http\Controllers\Controller;
use App\Http\Responses\ProblemResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Http\Requests\V1\MediaVariantRequest;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class MediaVariantController extends Controller
{
    /**
     * Browsers should not re-request the same variant for a year; the ETag
     * changes with the media row so updates naturally invalidate variants.
     */
    private const int MAX_AGE = 31536000;

    /**
     * Serve a resized variant of a stored image.
     *
     * The first request generates the variant, writes it under
     * variants/{id}/{version}-{width}-{format}.{ext} and streams it; the
     * timestamp in the path makes updated media produce fresh files while
     * old ones fall out of use. Later requests are served straight from
     * disk. Responses carry an ETag plus one-year public max-age.
     */
    public function __invoke(MediaVariantRequest $variantRequest, #[CurrentUser] Identity $currentUser, Media $media): HttpResponse|StreamedResponse|ProblemResponse
    {
        Gate::authorize('view', $media);

        if (! str_starts_with($media->mime_type, 'image/')) {
            return new ProblemResponse(
                typeKey: 'validation',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                detail: __('validation.media_not_image'),
            );
        }

        $format = $variantRequest->string('format')->toString() ?: 'webp';
        $updatedAt = $media->updated_at;
        $version = $updatedAt !== null ? $updatedAt->timestamp : 0;
        $width = $variantRequest->width();
        $etag = '"'.hash('xxh128', $version.'|'.$media->id.'|'.$width.'|'.$format).'"';

        if ($variantRequest->headers->get('If-None-Match') === $etag) {
            /** @var HttpResponse $notModified */
            $notModified = response('', Response::HTTP_NOT_MODIFIED, ['ETag' => $etag]);

            return $notModified;
        }

        $disk = Storage::disk($media->disk);
        $variantPath = 'variants/'.$media->id.'/'.$version.'-'.$width.'-'.$format.'.'.($format === 'jpg' ? 'jpg' : 'webp');

        if ($disk->exists($variantPath)) {
            /** @var StreamedResponse $cached */
            $cached = $disk->response($variantPath);

            return $cached->setEtag($etag)->setMaxAge(self::MAX_AGE)->setPublic();
        }

        $image = Image::fromStorage($media->path, $media->disk)
            ->scale(width: $width)
            ->toFormat($format)
            ->quality(80);
        $image->storeAs(dirname($variantPath), basename($variantPath), $media->disk, ['visibility' => $media->visibility->value]);

        return $image->toResponse($variantRequest)
            ->setEtag($etag)
            ->setMaxAge(self::MAX_AGE)
            ->setPublic();
    }
}
