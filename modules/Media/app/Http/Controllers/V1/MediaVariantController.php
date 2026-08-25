<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ProblemResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Image;
use Modules\IAM\Models\User;
use Modules\Media\Http\Requests\V1\MediaVariantRequest;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;

final readonly class MediaVariantController extends Controller
{
    /**
     * Browsers should not re-request the same variant for a year; the ETag
     * changes with the media row so updates naturally invalidate variants.
     */
    private const int MAX_AGE = 31536000;

    /**
     * Serve an on-the-fly resized variant of a stored image.
     *
     * The transformation never upscales: a width above the original size
     * yields the original dimensions. Variants are generated per request and
     * made cacheable through an ETag plus one-year public max-age.
     */
    public function __invoke(MediaVariantRequest $variantRequest, #[CurrentUser] User $currentUser, Media $media): HttpResponse|ProblemResponse
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
        $fingerprint = $version.'|'.$media->id.'|'.$variantRequest->width().'|'.$format;
        $etag = '"'.hash('xxh128', $fingerprint).'"';

        if ($variantRequest->headers->get('If-None-Match') === $etag) {
            /** @var HttpResponse $notModified */
            $notModified = response('', Response::HTTP_NOT_MODIFIED, ['ETag' => $etag]);

            return $notModified;
        }

        return Image::fromStorage($media->path, $media->disk)
            ->scale(width: $variantRequest->width())
            ->toFormat($format)
            ->quality(80)
            ->toResponse($variantRequest)
            ->setEtag($etag)
            ->setMaxAge(self::MAX_AGE)
            ->setPublic();
    }
}
